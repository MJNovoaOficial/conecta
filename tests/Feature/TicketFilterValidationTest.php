<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFilterValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtros_con_arrays_no_producen_500_ni_modifican_datos(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::create(['ticket_number' => 'FILTER-KEEP', 'user_id' => $owner->id, 'title' => 'Correo pendiente', 'description' => 'Conservar datos.', 'status' => 'open', 'priority' => 'medium']);
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs($owner);
        foreach (['search', 'status', 'priority', 'agent_id', 'categoria_id', 'requester_id', 'date_from', 'date_to', 'sort', 'dir', 'page'] as $field) {
            $this->getJson(route('tickets.index', [$field => ['valor no escalar']]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_fecha_invalida_y_pagina_invalida_se_rechazan_sin_bucle_en_el_navegador(): void
    {
        $this->actingAs(User::factory()->create());
        $bad = route('tickets.index', ['date_from' => '2026-02-31', 'page' => 'abc']);
        $this->from($bad)->get($bad)->assertRedirect(route('tickets.index'))
            ->assertSessionHasErrors(['date_from', 'page']);
        $this->get(route('tickets.index'))->assertOk();
    }

    public function test_filtros_normales_y_fallback_de_orden_no_amplian_el_acceso(): void
    {
        $owner = User::factory()->create();
        Ticket::create(['ticket_number' => 'FILTER-MINE', 'user_id' => $owner->id, 'title' => 'Correo de mi cuenta', 'status' => 'open', 'priority' => 'medium']);
        Ticket::create(['ticket_number' => 'FILTER-OTHER', 'user_id' => User::factory()->create()->id, 'title' => 'Correo de otra cuenta', 'status' => 'open', 'priority' => 'medium']);
        $this->actingAs($owner)->get(route('tickets.index', [
            'search' => 'Correo', 'status' => 'open', 'priority' => 'medium', 'sort' => 'orden-desconocido',
            'dir' => 'direccion-desconocida', 'date_from' => now()->toDateString(), 'date_to' => now()->toDateString(), 'page' => 1,
        ]))->assertOk()->assertSee('FILTER-MINE')->assertDontSee('FILTER-OTHER');
    }
}
