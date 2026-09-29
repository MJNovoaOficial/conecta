<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La clasificación (categoría → subcategoría → tipo) tiene que verse al
 * crear un ticket, sin depender de que alguien abra un acordeón marcado
 * "opcional".
 *
 * No es un detalle de estilo: PriorityRule::resolve() usa exactamente estos
 * campos para decidir la urgencia, y sin subcategoría todo ticket nace con
 * prioridad media, sea cual sea el problema real. Antes de este cambio, el
 * modal rápido y el formulario de invitados escondían la clasificación
 * detrás de "Más detalles (opcional)"; casi nadie la abría.
 *
 * La clasificación sigue sin ser obligatoria —eso no cambió—, así que estas
 * pruebas verifican visibilidad y orden, no que el envío la exija. Esa
 * decisión ya está probada en ClassificationHierarchyTest.
 */
class ClasificacionVisibleTest extends TestCase
{
    use RefreshDatabase;

    private function departamento(): Department
    {
        return Department::create(['name' => 'Soporte TI', 'is_active' => true]);
    }

    public function test_en_el_modal_rapido_la_clasificacion_va_antes_que_la_descripcion_y_sin_acordeon(): void
    {
        $this->departamento();
        $usuario = User::factory()->create();

        $html = $this->actingAs($usuario)->get(route('tickets.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(
            'Más detalles',
            $html,
            'la clasificación ya no debería estar marcada como "más detalles" opcional'
        );

        $posAsunto  = strpos($html, '¿Cuál es tu problema o solicitud?');
        $posCat     = strpos($html, 'id="modalCatSelect"');
        $posDesc    = strpos($html, 'Cuéntanos más');

        $this->assertNotFalse($posAsunto);
        $this->assertNotFalse($posCat);
        $this->assertNotFalse($posDesc);
        $this->assertTrue($posAsunto < $posCat, 'el asunto va primero');
        $this->assertTrue($posCat < $posDesc, 'la clasificación va antes que la descripción');
    }

    public function test_el_modal_rapido_explica_por_que_pide_la_clasificacion(): void
    {
        $this->departamento();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Ayúdanos a priorizar tu ticket', false);
    }

    public function test_en_el_formulario_de_invitado_la_clasificacion_va_antes_que_la_descripcion_y_sin_acordeon(): void
    {
        $this->departamento();

        $html = $this->get(route('tickets.guest.create'))->assertOk()->getContent();

        $this->assertStringNotContainsString('Más detalles', $html);

        $posAsunto = strpos($html, '¿Cuál es tu problema o solicitud?');
        $posCat    = strpos($html, 'id="guestCatSelect"');
        $posDesc   = strpos($html, 'Cuéntanos más');

        $this->assertNotFalse($posAsunto);
        $this->assertNotFalse($posCat);
        $this->assertNotFalse($posDesc);
        $this->assertTrue($posAsunto < $posCat);
        $this->assertTrue($posCat < $posDesc);
    }

    public function test_el_selector_de_categoria_no_esta_deshabilitado_de_entrada(): void
    {
        // La cascada subcategoría/tipo sí empieza deshabilitada —dependen de
        // elegir categoría primero—, pero categoría tiene que estar lista
        // para usarse apenas carga la página.
        $this->departamento();

        $html = $this->get(route('tickets.guest.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<select id="guestCatSelect"[^>]*>/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<select id="guestCatSelect"[^>]*disabled/',
            $html
        );
    }
}
