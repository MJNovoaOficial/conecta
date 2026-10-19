<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\ArticuloImagen;
use App\Models\AuditLog;
use App\Models\Manual;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ContentFileIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->actingAs(User::factory()->administrador()->create());
    }

    private function pdf(string $content = 'PDF de prueba'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('guia original.pdf', "%PDF-1.4\n{$content}\n%%EOF");
    }

    private function png(string $name = 'captura.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9l8AAAAASUVORK5CYII='
        ));
    }

    private function manual(string $path = 'manuales/anterior.pdf'): Manual
    {
        Storage::disk('local')->put($path, 'PDF anterior que debe conservarse');
        return Manual::create([
            'titulo' => 'Guía original', 'descripcion' => 'Conservar', 'categoria' => 'correo',
            'archivo_path' => $path, 'archivo_nombre_original' => 'anterior.pdf',
            'archivo_size' => 36, 'downloads_count' => 12, 'is_active' => true,
            'uploaded_by' => auth()->id(),
        ])->fresh();
    }

    private function article(): Articulo
    {
        return Articulo::create([
            'title' => 'Artículo original', 'content' => 'Pasos anteriores',
            'created_by' => auth()->id(), 'is_active' => true, 'publico' => false,
        ])->fresh();
    }

    private function image(Articulo $article, string $path = 'articulos/historica.png'): ArticuloImagen
    {
        Storage::disk('local')->put($path, 'Imagen histórica');
        return $article->imagenes()->create([
            'ruta' => $path, 'nombre_original' => 'historica.png', 'descripcion' => 'No perder', 'orden' => 3,
        ])->fresh();
    }

    private function articleData(array $extra = []): array
    {
        return array_merge(['title' => 'Artículo editado', 'content' => 'Pasos editados'], $extra);
    }

    private function failModelEvent(string $model, string $event, callable $request): void
    {
        $dispatcher = $model::getEventDispatcher();
        $name = "eloquent.{$event}: {$model}";
        $dispatcher->listen($name, fn () => throw new \RuntimeException('Fallo de persistencia simulado.'));
        try {
            $request();
        } finally {
            $dispatcher->forget($name);
        }
    }

    public function test_manuales_con_mismo_titulo_y_segundo_no_comparten_ni_sobrescriben_pdf(): void
    {
        $this->travelTo(now()->startOfSecond());
        foreach (['Primero', 'Segundo'] as $text) {
            $this->post(route('admin.manuales.store'), ['titulo' => 'Misma guía', 'archivo' => $this->pdf($text)])
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.manuales.index'));
        }
        $manuals = Manual::orderBy('id')->get();
        $this->assertCount(2, $manuals);
        $this->assertNotSame($manuals[0]->archivo_path, $manuals[1]->archivo_path);
        foreach ($manuals as $i => $manual) {
            $this->assertSame('guia original.pdf', $manual->archivo_nombre_original);
            $this->assertStringContainsString(['Primero', 'Segundo'][$i], Storage::disk('local')->get($manual->archivo_path));
        }
        $this->travelBack();
    }

    public function test_reemplazo_conserva_pdf_historico_compartido_y_otros_registros(): void
    {
        $first = $this->manual();
        $second = $this->manual();
        $otherBefore = $second->getAttributes();
        $this->put(route('admin.manuales.update', $first), [
            'titulo' => 'Guía nueva', 'is_active' => 1, 'archivo' => $this->pdf('Reemplazo'),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $first->refresh();
        $this->assertNotSame($second->archivo_path, $first->archivo_path);
        $this->assertSame(12, $first->downloads_count);
        $this->assertSame($otherBefore, $second->fresh()->getAttributes());
        Storage::disk('local')->assertExists([$first->archivo_path, $second->archivo_path]);
        $this->assertDatabaseCount('manuales', 2);
    }

    public function test_eliminar_un_manual_no_borra_pdf_mientras_otro_manual_lo_referencia(): void
    {
        $first = $this->manual();
        $second = $this->manual();
        $this->delete(route('admin.manuales.destroy', $first))->assertRedirect();
        $this->assertModelMissing($first);
        $this->assertModelExists($second);
        Storage::disk('local')->assertExists($second->archivo_path);
        $this->delete(route('admin.manuales.destroy', $second))->assertRedirect();
        Storage::disk('local')->assertMissing($second->archivo_path);
    }

    public function test_fallo_de_escritura_del_pdf_conserva_manual_y_archivo_anteriores(): void
    {
        $manual = $this->manual();
        $before = $manual->getAttributes();
        $disk = Storage::disk('local');
        $mock = Mockery::mock($disk)->makePartial();
        $mock->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('local', $mock);
        $this->put(route('admin.manuales.update', $manual), [
            'titulo' => 'No guardar', 'is_active' => 1, 'archivo' => $this->pdf(),
        ])->assertSessionHasErrors('archivo');
        $this->assertSame($before, $manual->fresh()->getAttributes());
        $this->assertSame([$manual->archivo_path], $disk->allFiles());
    }

    public function test_fallo_de_guardado_del_manual_limpia_solo_pdf_nuevo_y_conserva_el_anterior(): void
    {
        $manual = $this->manual();
        $before = $manual->getAttributes();
        $this->failModelEvent(Manual::class, 'updating', function () use ($manual) {
            $this->put(route('admin.manuales.update', $manual), [
                'titulo' => 'No guardar', 'is_active' => 1, 'archivo' => $this->pdf(),
            ])->assertStatus(500);
        });
        $this->assertSame($before, $manual->fresh()->getAttributes());
        $this->assertSame([$manual->archivo_path], Storage::disk('local')->allFiles());
    }

    public function test_fallo_de_alta_de_manual_no_deja_fila_ni_pdf_nuevo(): void
    {
        $this->failModelEvent(Manual::class, 'creating', function () {
            $this->post(route('admin.manuales.store'), ['titulo' => 'No guardar', 'archivo' => $this->pdf()])->assertStatus(500);
        });
        $this->assertDatabaseCount('manuales', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_fallo_al_eliminar_manual_conserva_su_fila_y_pdf(): void
    {
        $manual = $this->manual();
        $before = $manual->getAttributes();
        $this->failModelEvent(Manual::class, 'deleting', fn () => $this->delete(route('admin.manuales.destroy', $manual))->assertStatus(500));
        $this->assertSame($before, $manual->fresh()->getAttributes());
        Storage::disk('local')->assertExists($manual->archivo_path);
    }

    public function test_reemplazo_correcto_retira_solo_pdf_anterior_sin_referencias(): void
    {
        $manual = $this->manual();
        $other = $this->manual('manuales/otro.pdf');
        $oldPath = $manual->archivo_path;
        $this->put(route('admin.manuales.update', $manual), [
            'titulo' => 'Actualizada', 'is_active' => 1, 'archivo' => $this->pdf(),
        ])->assertSessionHasNoErrors()->assertRedirect();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists([$manual->fresh()->archivo_path, $other->archivo_path]);
        $this->assertDatabaseCount('manuales', 2);
    }

    public function test_fallo_de_limpieza_del_pdf_no_convierte_guardado_confirmado_en_error(): void
    {
        $manual = $this->manual();
        $oldPath = $manual->archivo_path;
        $disk = Storage::disk('local');
        $mock = Mockery::mock($disk)->makePartial();
        $mock->shouldReceive('delete')->with($oldPath)->once()->andThrow(new \RuntimeException('Archivo ocupado.'));
        Storage::set('local', $mock);
        $this->put(route('admin.manuales.update', $manual), [
            'titulo' => 'Actualizada', 'is_active' => 1, 'archivo' => $this->pdf(),
        ])->assertSessionHasNoErrors()->assertSessionHas('success')->assertRedirect();
        $this->assertSame('Actualizada', $manual->fresh()->titulo);
        $this->assertNotSame($oldPath, $manual->fresh()->archivo_path);
        $disk->assertExists([$oldPath, $manual->fresh()->archivo_path]);
        $this->assertDatabaseCount('manuales', 1);
    }

    public function test_imagen_invalida_en_alta_y_reintento_no_crea_articulos_parciales(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->post(route('admin.articulos.store'), $this->articleData([
                'imagenes' => [UploadedFile::fake()->createWithContent('invalida.txt', 'No es imagen')],
            ]))->assertSessionHasErrors('imagenes.0');
        }
        $this->assertDatabaseCount('articulos', 0);
        $this->assertDatabaseCount('articulo_imagenes', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_imagen_invalida_en_edicion_no_cambia_texto_ni_borra_imagen_seleccionada(): void
    {
        $article = $this->article();
        $image = $this->image($article);
        $before = $article->getAttributes();
        $imageBefore = $image->getAttributes();
        $this->put(route('admin.articulos.update', $article), $this->articleData([
            'publico' => 1, 'eliminar_imagen' => [$image->id],
            'imagenes' => [UploadedFile::fake()->createWithContent('invalida.txt', 'No es imagen')],
        ]))->assertSessionHasErrors('imagenes.0');
        $this->assertSame($before, $article->fresh()->getAttributes());
        $this->assertSame($imageBefore, $image->fresh()->getAttributes());
        Storage::disk('local')->assertExists($image->ruta);
    }

    public function test_metadatos_invalidos_no_modifican_articulo_ni_imagenes(): void
    {
        $article = $this->article();
        $image = $this->image($article);
        $before = $article->getAttributes();
        $imageBefore = $image->getAttributes();
        foreach ([
            ['imagen_descripcion' => [$image->id => ['dato inesperado']]],
            ['imagen_descripcion' => [$image->id => str_repeat('x', 301)]],
            ['imagen_orden' => [$image->id => -1]],
            ['imagen_orden' => [$image->id => 1000]],
            ['eliminar_imagen' => [['dato inesperado']]],
        ] as $extra) {
            $this->put(route('admin.articulos.update', $article), $this->articleData($extra))->assertSessionHasErrors();
            $this->assertSame($before, $article->fresh()->getAttributes());
            $this->assertSame($imageBefore, $image->fresh()->getAttributes());
        }
        Storage::disk('local')->assertExists($image->ruta);
    }

    public function test_no_se_pueden_editar_ni_retirar_imagenes_de_otro_articulo(): void
    {
        $article = $this->article();
        $other = $this->article();
        $image = $this->image($other);
        $before = $article->getAttributes();
        $imageBefore = $image->getAttributes();
        foreach ([['imagen_descripcion' => [$image->id => 'Ajena']], ['eliminar_imagen' => [$image->id]]] as $extra) {
            $this->put(route('admin.articulos.update', $article), $this->articleData($extra))->assertSessionHasErrors();
            $this->assertSame($before, $article->fresh()->getAttributes());
            $this->assertSame($imageBefore, $image->fresh()->getAttributes());
        }
        Storage::disk('local')->assertExists($image->ruta);
    }

    public function test_alta_valida_guarda_imagenes_privadas_unicas_y_auditoria(): void
    {
        $this->post(route('admin.articulos.store'), $this->articleData([
            'imagenes' => [$this->png(), $this->png()],
        ]))->assertSessionHasNoErrors()->assertSessionHas('success');
        $article = Articulo::sole();
        $this->assertFalse($article->publico);
        $this->assertCount(2, $images = $article->imagenes()->get());
        $this->assertNotSame($images[0]->ruta, $images[1]->ruta);
        $this->assertSame('captura.png', $images[0]->nombre_original);
        Storage::disk('local')->assertExists($images->pluck('ruta')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'articulo.created', 'model_id' => $article->id]);
    }

    public function test_edicion_valida_retira_solo_imagen_marcada_y_conserva_otra_y_sus_metadatos(): void
    {
        $article = $this->article();
        $remove = $this->image($article, 'articulos/retirar.png');
        $keep = $this->image($article, 'articulos/conservar.png');
        $this->put(route('admin.articulos.update', $article), $this->articleData([
            'eliminar_imagen' => [$remove->id], 'imagenes' => [$this->png()],
            'imagen_descripcion' => [$keep->id => 'Descripción actualizada'], 'imagen_orden' => [$keep->id => 8],
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.articulos.index'));
        $this->assertModelMissing($remove);
        Storage::disk('local')->assertMissing($remove->ruta);
        Storage::disk('local')->assertExists($keep->ruta);
        $this->assertSame('Descripción actualizada', $keep->fresh()->descripcion);
        $this->assertSame(8, (int) $keep->fresh()->orden);
        $this->assertSame('Artículo editado', $article->fresh()->title);
        $this->assertCount(2, $article->imagenes()->get());
        $this->assertDatabaseHas('audit_logs', ['action' => 'articulo.updated', 'model_id' => $article->id]);
    }

    public function test_fallo_al_escribir_segunda_imagen_revierte_todo_y_limpia_solo_archivos_nuevos(): void
    {
        $article = $this->article();
        $image = $this->image($article);
        $before = $article->getAttributes();
        $imageBefore = $image->getAttributes();
        $disk = Storage::disk('local');
        $mock = Mockery::mock($disk)->makePartial();
        $calls = 0;
        $mock->shouldReceive('putFileAs')->twice()->andReturnUsing(function (...$args) use (&$calls, $disk) {
            return ++$calls === 1 ? $disk->putFileAs(...$args) : false;
        });
        Storage::set('local', $mock);
        $this->put(route('admin.articulos.update', $article), $this->articleData([
            'eliminar_imagen' => [$image->id], 'imagenes' => [$this->png(), $this->png()],
        ]))->assertSessionHasErrors('imagenes');
        $this->assertSame($before, $article->fresh()->getAttributes());
        $this->assertSame($imageBefore, $image->fresh()->getAttributes());
        $this->assertSame([$image->ruta], $disk->allFiles());
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_fallo_de_auditoria_en_alta_revierte_articulo_imagenes_y_archivos_nuevos(): void
    {
        $this->failModelEvent(AuditLog::class, 'creating', function () {
            $this->post(route('admin.articulos.store'), $this->articleData(['imagenes' => [$this->png()]]))->assertStatus(500);
        });
        $this->assertDatabaseCount('articulos', 0);
        $this->assertDatabaseCount('articulo_imagenes', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_fallo_de_persistencia_en_edicion_conserva_texto_imagenes_y_archivos_anteriores(): void
    {
        $article = $this->article();
        $image = $this->image($article);
        $before = $article->getAttributes();
        $imageBefore = $image->getAttributes();
        $this->failModelEvent(ArticuloImagen::class, 'creating', function () use ($article, $image) {
            $this->put(route('admin.articulos.update', $article), $this->articleData([
                'eliminar_imagen' => [$image->id], 'imagenes' => [$this->png()], 'publico' => 1,
            ]))->assertStatus(500);
        });
        $this->assertSame($before, $article->fresh()->getAttributes());
        $this->assertSame($imageBefore, $image->fresh()->getAttributes());
        $this->assertSame([$image->ruta], Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_retirar_imagen_no_borra_archivo_compartido_por_otro_registro_historico(): void
    {
        $article = $this->article();
        $other = $this->article();
        $image = $this->image($article);
        $shared = $this->image($other);
        $this->put(route('admin.articulos.update', $article), $this->articleData([
            'eliminar_imagen' => [$image->id],
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertModelMissing($image);
        $this->assertModelExists($shared);
        Storage::disk('local')->assertExists($shared->ruta);
    }

    public function test_fallo_de_limpieza_de_imagen_no_convierte_edicion_confirmada_en_error(): void
    {
        $article = $this->article();
        $image = $this->image($article);
        $disk = Storage::disk('local');
        $mock = Mockery::mock($disk)->makePartial();
        $mock->shouldReceive('delete')->with($image->ruta)->once()->andThrow(new \RuntimeException('Archivo ocupado.'));
        Storage::set('local', $mock);
        $this->put(route('admin.articulos.update', $article), $this->articleData([
            'eliminar_imagen' => [$image->id],
        ]))->assertSessionHasNoErrors()->assertSessionHas('success')->assertRedirect();
        $this->assertSame('Artículo editado', $article->fresh()->title);
        $this->assertModelMissing($image);
        $disk->assertExists($image->ruta);
        $this->assertDatabaseCount('articulos', 1);
    }

    public function test_filtros_malformados_de_articulos_devuelven_validacion_sin_modificar_datos(): void
    {
        $article = $this->article();
        $before = $article->getAttributes();
        foreach (['q', 'categoria_id', 'estado', 'page'] as $field) {
            $url = route('admin.articulos.index', [$field => ['valor inesperado']]);
            $this->getJson($url)->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame($before, $article->fresh()->getAttributes());
        }
        $url = route('admin.articulos.index', ['q' => ['valor inesperado']]);
        $this->from($url)->get($url)->assertRedirect(route('admin.articulos.index'))->assertSessionHasErrors('q');
    }
}
