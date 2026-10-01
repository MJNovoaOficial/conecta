<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormularioTicketGlobalTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_formulario_actual_es_unico_y_global_para_todos_los_roles(): void
    {
        foreach (['user', 'support', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $routes = ['tickets.index', 'profile.index', 'ayuda.index', 'tickets.create'];
            if ($role !== 'user') {
                $routes[] = 'tickets.my-stats';
            }

            foreach ($routes as $route) {
                $html = $this->get(route($route))->assertOk()->getContent();
                $this->assertSame(1, substr_count($html, 'id="newTicketModal"'));
                $this->assertSame(1, substr_count($html, 'id="modalTicketForm"'));
                $this->assertStringContainsString('¿En qué te podemos ayudar?', $html);
                $this->assertStringContainsString('action="'.route('tickets.store').'"', $html);
                $this->assertStringContainsString('name="_token"', $html);
                $this->assertStringNotContainsString('name="department_id"', $html);
                $this->assertStringNotContainsString('name="device_type"', $html);
                $this->assertMatchesRegularExpression('/id="burAbrirTicket"\s+data-bs-toggle="modal" data-bs-target="#newTicketModal"/', $html);
            }
        }
    }

    public function test_la_ruta_directa_abre_el_mismo_formulario_sin_el_editor_anterior(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tickets.create'))->assertOk()
            ->assertSee('bootstrap.Modal.getOrCreateInstance', false)
            ->assertSee('id="modalDescField"', false)
            ->assertDontSee('contenteditable="true"', false);
    }

    public function test_el_invitado_conserva_su_formulario_y_no_recibe_el_modal_autenticado(): void
    {
        $this->get(route('tickets.guest.create'))->assertOk()
            ->assertSee('name="guest_name"', false)
            ->assertSee('name="guest_email"', false)
            ->assertSee('action="'.route('tickets.guest.store').'"', false)
            ->assertDontSee('id="newTicketModal"', false)
            ->assertDontSee('id="burAbrirTicket"', false);

        $this->get(route('tickets.create'))->assertRedirect(route('login'));
    }
}
