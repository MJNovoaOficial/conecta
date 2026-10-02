<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\User;
use App\Services\AsistenteIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsistenteCortesiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_orientacion_aparece_en_el_saludo_y_no_en_otras_cortesias(): void
    {
        Http::preventStrayRequests();
        foreach ([false, true] as $publico) {
            foreach (['Hola', 'Hola, ¿cómo estás?'] as $pregunta) {
                $texto = app(AsistenteIA::class)->responder($pregunta, soloPublicos: $publico)['texto'];
                $this->assertStringContainsString('¿Qué problema o solicitud tienes?', $texto);
                $this->assertStringContainsString('Escríbelo con tus palabras', $texto);
                $this->assertStringContainsString($publico ? 'no recuerdo mi usuario' : 'no puedo imprimir', $texto);
                $this->assertStringNotContainsString($publico ? 'no puedo imprimir' : 'no recuerdo mi usuario', $texto);
            }
            foreach (['Gracias', 'Hasta luego'] as $pregunta) {
                $this->assertStringNotContainsString('Escríbelo con tus palabras',
                    app(AsistenteIA::class)->responder($pregunta, soloPublicos: $publico)['texto']);
            }
        }
        $this->assertStringNotContainsString('Escríbelo con tus palabras',
            file_get_contents(resource_path('views/partials/burbuja_ayuda.blade.php')));
        Http::assertNothingSent();
    }

    public function test_saludos_y_cortesia_funcionan_sin_consultar_ollama(): void
    {
        Http::preventStrayRequests();
        $asistente = app(AsistenteIA::class);

        foreach ([false, true] as $enabled) {
            config(['chatbot.enabled' => $enabled]);
            foreach (['hola', '¡Hola! ¿Cómo estás?', ' Buenos días ', 'Buenas tardes', 'Buenas noches', 'Muchas gracias', 'Gracias por tu ayuda', 'Adiós', 'Hasta luego'] as $mensaje) {
                foreach ([false, true] as $publico) {
                    $resultado = $asistente->responder($mensaje, soloPublicos: $publico);
                    $this->assertSame(AsistenteIA::CORTESIA, $resultado['tipo']);
                    $this->assertNotEmpty($resultado['texto']);
                    $this->assertTrue($resultado['fuentes']->isEmpty());
                }
            }
        }

        Http::assertNothingSent();
    }

    public function test_ambas_rutas_entregan_cortesia_sin_fuentes_y_conservan_la_autenticacion(): void
    {
        $this->postJson(route('asistente.publico'), ['pregunta' => 'Hola, ¿cómo estás?'])
            ->assertOk()->assertJsonPath('tipo', 'cortesia')->assertJsonPath('fuentes', []);

        $this->postJson(route('ayuda.asistente'), ['pregunta' => 'Hola'])->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->postJson(route('ayuda.asistente'), ['pregunta' => 'Hola'])
            ->assertOk()->assertJsonPath('tipo', 'cortesia')->assertJsonPath('fuentes', []);
    }

    public function test_un_saludo_o_agradecimiento_con_un_problema_no_se_confunde_con_cortesia(): void
    {
        config(['chatbot.enabled' => false]);
        $articulo = Articulo::create([
            'title' => 'La impresora no imprime',
            'symptoms' => 'impresora no imprime',
            'content' => 'Revisa que la impresora tenga papel.',
            'is_active' => true,
            'publico' => false,
        ]);

        foreach (['Hola, la impresora no imprime', 'Gracias, pero la impresora no imprime'] as $mensaje) {
            $resultado = app(AsistenteIA::class)->responder($mensaje);
            $this->assertSame(AsistenteIA::SOLO_ARTICULOS, $resultado['tipo']);
            $this->assertSame($articulo->id, $resultado['fuentes']->first()->id);

            $publico = app(AsistenteIA::class)->responder($mensaje, soloPublicos: true);
            $this->assertSame(AsistenteIA::SIN_COBERTURA, $publico['tipo']);
            $this->assertTrue($publico['fuentes']->isEmpty());
        }
    }
}
