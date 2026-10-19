<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UiEventHandlersTest extends TestCase
{
    use RefreshDatabase;

    private function document(string $html): \DOMXPath
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return new \DOMXPath($dom);
    }

    public static function categoryDescriptions(): array
    {
        return [
            'una línea' => ['Texto habitual', true],
            'multilínea y comillas' => ["Primera línea\nSegunda: O'Hara, \"TI\", C:\\equipos\\", false],
            'texto parecido a código' => ["Primera línea\n</script><img src=x onerror=alert(1)> & \"comillas\"", true],
            'descripción vacía' => [null, true],
        ];
    }

    #[DataProvider('categoryDescriptions')]
    public function test_editar_categoria_transporta_texto_sin_ejecutarlo_ni_reescribir_datos(?string $description, bool $active): void
    {
        $cat = Categoria::create(['name' => "Equipo d'Operaciones \"TI\"", 'description' => $description, 'is_active' => $active]);
        $before = $cat->fresh()->getAttributes();
        $html = $this->actingAs(User::factory()->administrador()->create())->get(route('admin.categories.index'))->assertOk()->getContent();
        $xpath = $this->document($html);
        $buttons = $xpath->query('//button[@data-category-name and @data-category-description]');
        $this->assertSame(1, $buttons->length);
        $button = $buttons->item(0);
        $this->assertSame($cat->name, $button->getAttribute('data-category-name'));
        $this->assertSame($description ?? '', $button->getAttribute('data-category-description'));
        $this->assertSame('button', $button->getAttribute('type'));
        $this->assertSame('openEditCat('.$cat->id.', this.dataset.categoryName, this.dataset.categoryDescription, '.($active ? 'true' : 'false').')', $button->getAttribute('onclick'));
        $this->assertSame($before, $cat->fresh()->getAttributes());
        $this->assertSame(0, $xpath->query('//img[@onerror]')->length);
        $hidden = $xpath->query('//form[@id="form-edit-cat"]//input[@type="hidden" and @name="is_active"]');
        $this->assertSame(1, $hidden->length);
        $this->assertSame('0', $hidden->item(0)->getAttribute('value'));
    }

    public function test_guardar_edicion_conserva_id_texto_multilinea_y_estado_sin_tocar_otra_categoria(): void
    {
        $cat = Categoria::create(['name' => 'Categoría original', 'description' => 'Antes', 'is_active' => true]);
        $other = Categoria::create(['name' => 'No modificar', 'description' => 'Conservar', 'is_active' => true]);
        $before = $other->fresh()->getAttributes();
        $description = "Primera línea\nSegunda con 'comillas' y \"texto\".";
        $this->actingAs(User::factory()->administrador()->create())->put(route('admin.categories.update', $cat), [
            'name' => "Equipo d'Operaciones", 'description' => $description, 'is_active' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $cat->refresh();
        $this->assertSame($description, $cat->description);
        $this->assertSame("Equipo d'Operaciones", $cat->name);
        $this->assertFalse((bool) $cat->is_active);
        $this->assertSame($before, $other->fresh()->getAttributes());
        $this->assertDatabaseCount('categorias', 2);
    }

    public static function readStates(): array
    {
        return ['sin leer' => [false], 'leída' => [true]];
    }

    #[DataProvider('readStates')]
    public function test_ver_ticket_conserva_eventos_formulario_y_marcado_de_lectura(bool $read): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create(['ticket_number' => 'UI-EVENT-TEST', 'user_id' => $user->id,
            'title' => 'Sin conexión', 'description' => 'No modificar datos.', 'status' => 'open', 'priority' => 'medium']);
        $before = $ticket->fresh()->getAttributes();
        $notice = Notificacion::create(['user_id' => $user->id, 'ticket_id' => $ticket->id, 'type' => 'comment',
            'title' => 'Aviso de prueba', 'read_at' => $read ? now()->subMinute() : null]);
        $html = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();
        $xpath = $this->document($html);
        $forms = $xpath->query('//form[@action="'.route('notifications.read', $notice).'"]');
        $this->assertSame(1, $forms->length);
        $form = $forms->item(0);
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)->length);
        $button = $xpath->query('.//button', $form)->item(0);
        $this->assertSame("this.style.opacity='.85'", $button->getAttribute('onmouseenter'));
        $this->assertSame("this.style.opacity='1'", $button->getAttribute('onmouseleave'));
        $this->assertSame('submit', $button->getAttribute('type'));
        $this->assertSame($read, $notice->fresh()->isRead());
        $this->post(route('notifications.read', $notice))->assertRedirect(route('tickets.show', $ticket));
        $this->assertTrue($notice->fresh()->isRead());
        $this->assertSame($before, $ticket->fresh()->getAttributes());
    }
}
