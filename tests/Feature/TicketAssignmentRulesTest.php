<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketAssignmentRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function ticket(?User $agent = null, string $status = Ticket::STATUS_OPEN): Ticket
    {
        $department = Department::create(['name' => 'Soporte '.fake()->unique()->numerify('########'), 'is_active' => true]);
        return Ticket::create([
            'ticket_number' => 'ASG-'.fake()->unique()->numerify('########'),
            'user_id' => User::factory()->create()->id, 'department_id' => $department->id,
            'assigned_to' => $agent?->id, 'title' => 'Consulta de prueba',
            'description' => 'Descripción que debe conservarse.', 'status' => $status, 'priority' => 'medium',
        ]);
    }

    private function assignmentForms(string $html, Ticket $ticket): int
    {
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $count = 0;
        foreach ((new \DOMXPath($document))->query('//form') as $form) {
            if (preg_match('~/tickets/'.$ticket->id.'/(?:self-assign|assign|forward)$~', $form->getAttribute('action'))) $count++;
        }
        return $count;
    }

    public function test_un_cerrado_no_se_asigna_reasigna_o_deriva_incluso_por_admin_y_conserva_su_historial(): void
    {
        $assigned = User::factory()->soporte()->create();
        $other = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $ticket = $this->ticket($assigned, Ticket::STATUS_CLOSED);
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $assigned->id, 'comment' => 'Mensaje previo.']);
        TicketHistory::create(['ticket_id' => $ticket->id, 'user_id' => $assigned->id, 'action' => 'closed']);
        Notificacion::notify($assigned->id, 'closed', 'Registro previo', '', $ticket->id);
        $before = $ticket->fresh()->getAttributes();
        foreach ([$assigned, $other, $admin] as $actor) {
            foreach ([
                ['tickets.selfAssign', []],
                ['tickets.assignTo', ['user_id' => $other->id]],
                ['tickets.forward', ['department_id' => $ticket->department_id, 'comment' => 'No debe guardarse.']],
            ] as [$route, $payload]) {
                $this->actingAs($actor)->post(route($route, $ticket), $payload)->assertSessionHas('error');
                $this->assertSame($before, $ticket->fresh()->getAttributes());
                $this->assertDatabaseCount('historial_ticket', 1);
                $this->assertDatabaseCount('comentarios_ticket', 1);
                $this->assertDatabaseCount('notificaciones', 1);
            }
        }
        Notification::assertNothingSent();
    }

    public function test_un_cerrado_libre_tampoco_puede_ser_tomado_por_admin(): void
    {
        $ticket = $this->ticket(null, Ticket::STATUS_CLOSED);
        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('tickets.selfAssign', $ticket))->assertSessionHas('error');
        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
    }

    public function test_otro_soporte_no_puede_tomar_reasignar_ni_derivar_un_ticket_en_atencion(): void
    {
        $assigned = User::factory()->soporte()->create();
        $other = User::factory()->soporte()->create();
        $ticket = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        $before = $ticket->fresh()->getAttributes();
        foreach ([['tickets.selfAssign', []], ['tickets.assignTo', ['user_id' => $other->id]], ['tickets.forward', ['department_id' => $ticket->department_id]]] as [$route, $payload]) {
            $this->actingAs($other)->post(route($route, $ticket), $payload)->assertSessionHas('error');
            $this->assertSame($before, $ticket->fresh()->getAttributes());
            $this->assertDatabaseCount('historial_ticket', 0);
            $this->assertDatabaseCount('notificaciones', 0);
        }
    }

    public function test_el_asignado_puede_entregar_a_otro_y_no_recuperarlo_por_autoasignacion(): void
    {
        $assigned = User::factory()->soporte()->create();
        $next = User::factory()->soporte()->create();
        $ticket = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        $this->actingAs($assigned)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])->assertSessionHas('success');
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
        $this->actingAs($assigned)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('error');
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
        $this->assertDatabaseCount('historial_ticket', 1);
    }

    public function test_una_derivacion_del_asignado_libera_el_ticket_para_que_otro_soporte_lo_tome(): void
    {
        $assigned = User::factory()->soporte()->create();
        $destination = Department::create(['name' => 'Destino de prueba', 'is_active' => true]);
        $next = User::factory()->soporte()->create(['department_id' => $destination->id]);
        $ticket = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        $this->actingAs($assigned)->post(route('tickets.forward', $ticket), ['department_id' => $destination->id, 'comment' => 'Revisar en destino.'])->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertSame(Ticket::STATUS_FORWARDED, $ticket->fresh()->status);
        $this->actingAs($next)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
        $this->assertSame($destination->id, $ticket->fresh()->department_id);
        $this->assertDatabaseCount('comentarios_ticket', 1);
        $this->assertDatabaseCount('historial_ticket', 2);
    }

    public function test_tomar_un_libre_cambia_a_en_proceso_y_repetir_no_duplica_historial(): void
    {
        $first = User::factory()->soporte()->create();
        $other = User::factory()->soporte()->create();
        $ticket = $this->ticket();
        $this->actingAs($first)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->assertSame($first->id, $ticket->fresh()->assigned_to);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertDatabaseCount('historial_ticket', 2);
        $this->actingAs($first)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->assertDatabaseCount('historial_ticket', 2);
        $this->actingAs($other)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('error');
        $this->assertSame($first->id, $ticket->fresh()->assigned_to);
    }

    public function test_admin_conserva_la_reasignacion_de_un_ticket_no_cerrado(): void
    {
        $assigned = User::factory()->soporte()->create();
        $next = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $ticket = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        $this->actingAs($admin)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])->assertSessionHas('success');
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
        $this->actingAs($admin)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->assertSame($admin->id, $ticket->fresh()->assigned_to);
    }

    public function test_el_agente_de_un_ticket_asignado_pero_abierto_conserva_el_inicio_de_atencion(): void
    {
        $agent = User::factory()->soporte()->create();
        $ticket = $this->ticket($agent, Ticket::STATUS_OPEN);
        $this->actingAs($agent)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    public function test_el_cierre_que_ocurre_antes_del_update_impide_la_asignacion_sin_efectos_secundarios(): void
    {
        $agent = User::factory()->soporte()->create();
        $ticket = $this->ticket();
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use (&$injected, $ticket) {
            if (!$injected && str_starts_with(strtolower($query), 'update `tickets`')) {
                $injected = true;
                $connection->table('tickets')->where('id', $ticket->id)->update(['status' => Ticket::STATUS_CLOSED]);
            }
        });
        $this->actingAs($agent)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('error');
        $this->assertTrue($injected);
        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_el_agente_que_gana_la_carrera_no_es_sobrescrito_por_la_peticion_pendiente(): void
    {
        $loser = User::factory()->soporte()->create();
        $winner = User::factory()->soporte()->create();
        $ticket = $this->ticket();
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use (&$injected, $ticket, $winner) {
            if (!$injected && str_starts_with(strtolower($query), 'update `tickets`')) {
                $injected = true;
                $connection->table('tickets')->where('id', $ticket->id)->update(['assigned_to' => $winner->id, 'status' => Ticket::STATUS_IN_PROGRESS]);
            }
        });
        $this->actingAs($loser)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('error');
        $this->assertTrue($injected);
        $this->assertSame($winner->id, $ticket->fresh()->assigned_to);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_show_y_panel_ocultan_controles_de_asignacion_al_cerrar_y_para_soporte_no_asignado(): void
    {
        $assigned = User::factory()->soporte()->create();
        $other = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $closed = $this->ticket($assigned, Ticket::STATUS_CLOSED);
        foreach ([$assigned, $other, $admin] as $actor) {
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $response = $this->actingAs($actor)->get(route($route, $closed))->assertOk()->assertSee('no permite asignación ni derivación');
                $this->assertSame(0, $this->assignmentForms($response->getContent(), $closed));
            }
        }
        $active = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        foreach (['tickets.show', 'tickets.panel'] as $route) {
            $response = $this->actingAs($other)->get(route($route, $active))->assertOk();
            $this->assertSame(0, $this->assignmentForms($response->getContent(), $active));
            $response = $this->actingAs($assigned)->get(route($route, $active))->assertOk();
            $this->assertGreaterThan(0, $this->assignmentForms($response->getContent(), $active));
        }
    }

    public function test_una_reasignacion_pendiente_no_pisa_al_agente_que_cambio_mientras_se_validaba(): void
    {
        $assigned = User::factory()->soporte()->create();
        $destination = User::factory()->soporte()->create();
        $winner = User::factory()->soporte()->create();
        $ticket = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use (&$injected, $ticket, $winner) {
            if (!$injected && str_starts_with(strtolower($query), 'update `tickets`')) {
                $injected = true;
                $connection->table('tickets')->where('id', $ticket->id)->update(['assigned_to' => $winner->id]);
            }
        });
        $this->actingAs($assigned)->post(route('tickets.assignTo', $ticket), ['user_id' => $destination->id])->assertSessionHas('error');
        $this->assertTrue($injected);
        $this->assertSame($winner->id, $ticket->fresh()->assigned_to);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_una_derivacion_pendiente_no_reabre_ni_libera_un_ticket_que_acaba_de_cerrarse(): void
    {
        $assigned = User::factory()->soporte()->create();
        $ticket = $this->ticket($assigned, Ticket::STATUS_IN_PROGRESS);
        $destination = Department::create(['name' => 'Destino de carrera', 'is_active' => true]);
        $oldDepartment = $ticket->department_id;
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use (&$injected, $ticket) {
            if (!$injected && str_starts_with(strtolower($query), 'update `tickets`')) {
                $injected = true;
                $connection->table('tickets')->where('id', $ticket->id)->update(['status' => Ticket::STATUS_CLOSED]);
            }
        });
        $this->actingAs($assigned)->post(route('tickets.forward', $ticket), ['department_id' => $destination->id, 'comment' => 'No debe agregarse.'])->assertSessionHas('error');
        $this->assertTrue($injected);
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertSame($assigned->id, $ticket->fresh()->assigned_to);
        $this->assertSame($oldDepartment, $ticket->fresh()->department_id);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }
}
