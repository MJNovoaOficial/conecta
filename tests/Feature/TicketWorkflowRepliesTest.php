<?php

namespace Tests\Feature;

use App\Jobs\AutoCloseTicketJob;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketWorkflowRepliesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        $this->travelTo(now()->startOfSecond());
    }

    private function ticket(array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'FLOW-'.fake()->unique()->numerify('##########'),
            'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->soporte()->create()->id,
            'status' => Ticket::STATUS_IN_PROGRESS, 'priority' => 'medium',
            'title' => 'Revisión del flujo', 'description' => 'Descripción conservada.',
        ], $extra));
    }

    public function test_la_respuesta_publica_del_asignado_espera_al_usuario_y_su_respuesta_reanuda_la_atencion(): void
    {
        foreach (['support', 'admin'] as $role) {
            $agent = User::factory()->create(['role' => $role]);
            $ticket = $this->ticket(['assigned_to' => $agent->id]);
            $this->actingAs($agent)->post(route('tickets.addComment', $ticket), ['comment' => 'Revise el envío de correo, por favor.'])->assertSessionHasNoErrors();
            $ticket->refresh();
            $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->status);
            $this->assertNotNull($ticket->last_response_request_at);
            $this->assertNull($ticket->response_deadline_at);
            $this->assertNull($ticket->user_responded_at);
            $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('data-bs-target="#requestInfoModal"', false);
            $this->assertDatabaseHas('historial_ticket', ['ticket_id' => $ticket->id, 'action' => 'status_change', 'old_value' => Ticket::STATUS_IN_PROGRESS, 'new_value' => Ticket::STATUS_PENDING_USER]);
            $this->assertDatabaseHas('notificaciones', ['ticket_id' => $ticket->id, 'user_id' => $ticket->user_id, 'type' => 'comment']);
            $this->travel(3)->hours();
            (new AutoCloseTicketJob)->handle();
            $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
            $this->flushSession();
            $this->actingAs($ticket->user)->post(route('tickets.addComment', $ticket), ['comment' => 'Todavía tengo problemas para enviar.'])->assertSessionHasNoErrors()->assertSessionHas('success');
            $ticket->refresh();
            $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);
            $this->assertNotNull($ticket->user_responded_at);
            $this->assertNull($ticket->response_deadline_at);
            $this->assertSame($agent->id, $ticket->assigned_to);
            $this->assertSame('Descripción conservada.', $ticket->description);
            $this->assertSame(2, $ticket->comments()->count());
        }
    }

    public function test_las_notas_internas_no_cambian_el_turno_ni_los_plazos_y_el_solicitante_no_las_ve(): void
    {
        foreach ([Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING_USER] as $status) {
            $ticket = $this->ticket(['status' => $status, 'response_deadline_at' => $status === Ticket::STATUS_PENDING_USER ? now()->addHours(2) : null]);
            $before = $ticket->fresh()->getAttributes();
            $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), ['comment' => 'Nota técnica privada.', 'is_internal' => 1])->assertSessionHasNoErrors();
            $this->assertSame($before, $ticket->fresh()->getAttributes());
            $this->actingAs($ticket->user)->get(route('tickets.show', $ticket))->assertDontSee('Nota técnica privada.');
        }
    }

    public function test_la_solicitud_explicita_conserva_su_plazo_y_un_mensaje_adicional_no_lo_reinicia(): void
    {
        $ticket = $this->ticket();
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), ['comment' => 'Necesitamos una captura para continuar.', 'request_info' => 1])->assertSessionHas('success');
        $ticket->refresh();
        $deadline = $ticket->response_deadline_at;
        $requestedAt = $ticket->last_response_request_at;
        $this->assertNotNull($deadline);
        $this->travel(10)->minutes();
        $this->post(route('tickets.addComment', $ticket), ['comment' => 'Incluya también el mensaje de error.'])->assertSessionHasNoErrors();
        $this->assertTrue($deadline->equalTo($ticket->fresh()->response_deadline_at));
        $this->assertTrue($requestedAt->equalTo($ticket->fresh()->last_response_request_at));
        $this->actingAs($ticket->user)->post(route('tickets.addComment', $ticket), ['comment' => 'Envío los detalles solicitados.'])->assertSessionHasNoErrors();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->response_deadline_at);
    }

    public function test_el_invitado_responde_al_mensaje_normal_y_reanuda_la_atencion_sin_login(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest-flow@example.test', 'guest_token' => str_repeat('w', 40)]);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), ['comment' => 'Pruebe nuevamente y confirme.'])->assertSessionHasNoErrors();
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
        Auth::logout();
        $this->post(route('tickets.guest.comment', $ticket->guest_token), ['comment' => 'Envío la información solicitada.'])->assertSessionHasNoErrors();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->user_responded_at);
        $this->assertNull($ticket->fresh()->response_deadline_at);
    }

    public function test_admin_no_asignado_no_puede_contestar_ni_tomar_ni_transferir_un_ticket_ajeno(): void
    {
        $ticket = $this->ticket();
        $admin = User::factory()->administrador()->create();
        $next = User::factory()->soporte()->create();
        $department = Department::create(['name' => 'Destino del flujo', 'is_active' => true]);
        $before = $ticket->fresh()->getAttributes();
        foreach ([[], ['is_internal' => 1], ['request_info' => 1]] as $extra) {
            $this->actingAs($admin)->post(route('tickets.addComment', $ticket), ['comment' => 'No debe guardarse.'] + $extra)->assertForbidden();
        }
        foreach ([['tickets.selfAssign', []], ['tickets.assignTo', ['user_id' => $next->id]], ['tickets.forward', ['department_id' => $department->id]]] as [$route, $payload]) {
            $this->actingAs($admin)->post(route($route, $ticket), $payload)->assertSessionHas('error');
        }
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public static function finishedStates(): array
    {
        return ['resuelto' => [Ticket::STATUS_RESOLVED], 'cerrado' => [Ticket::STATUS_CLOSED]];
    }

    #[DataProvider('finishedStates')]
    public function test_un_terminado_no_se_asigna_ni_deriva_por_ningun_agente_y_no_renderiza_los_formularios(string $status): void
    {
        $assigned = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $ticket = $this->ticket(['assigned_to' => $assigned->id, 'status' => $status, 'resolved_at' => now(), 'solution_text' => 'Se corrigió y verificó el problema.']);
        $before = $ticket->fresh()->getAttributes();
        foreach ([$assigned, $admin] as $actor) {
            foreach ([['tickets.selfAssign', []], ['tickets.assignTo', ['user_id' => $admin->id]], ['tickets.forward', ['department_id' => 999]]] as [$route, $payload]) {
                $this->actingAs($actor)->post(route($route, $ticket), $payload)->assertSessionHas('error');
                $this->assertSame($before, $ticket->fresh()->getAttributes());
            }
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $html = $this->actingAs($actor)->get(route($route, $ticket))->assertOk()->getContent();
                $this->assertDoesNotMatchRegularExpression('~action="[^"]*/tickets/'.$ticket->id.'/(?:self-assign|assign|forward)"~', $html);
                $this->assertStringNotContainsString('Reasignarme', $html);
            }
        }
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_otro_agente_solo_responde_despues_de_la_transferencia_y_el_anterior_deja_de_poder_responder(): void
    {
        $first = User::factory()->administrador()->create();
        $next = User::factory()->soporte()->create();
        $ticket = $this->ticket(['assigned_to' => $first->id]);
        $this->actingAs($first)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])->assertSessionHas('success');
        $this->post(route('tickets.addComment', $ticket), ['comment' => 'Ya no debo intervenir.'])->assertForbidden();
        $this->actingAs($next)->post(route('tickets.addComment', $ticket), ['comment' => 'Asumo la atención del caso.'])->assertSessionHasNoErrors();
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->comments()->count());
    }

    public function test_una_respuesta_rechazada_por_cambio_de_asignacion_no_se_guarda_ni_cambia_el_turno(): void
    {
        $ticket = $this->ticket();
        $winner = User::factory()->soporte()->create();
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use (&$injected, $ticket, $winner) {
            if (!$injected && str_starts_with(strtolower($query), 'update `tickets`')) {
                $injected = true;
                $connection->table('tickets')->where('id', $ticket->id)->update(['assigned_to' => $winner->id]);
            }
        });
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), ['comment' => 'Mi formulario quedó desactualizado.'])->assertSessionHasErrors('comment');
        $this->assertTrue($injected);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame($winner->id, $ticket->fresh()->assigned_to);
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_si_falla_el_guardado_del_mensaje_no_queda_un_cambio_de_estado_sin_respuesta(): void
    {
        $ticket = $this->ticket();
        $before = $ticket->fresh()->getAttributes();
        $injected = false;
        $this->withoutExceptionHandling();
        DB::connection()->beforeExecuting(function ($query) use (&$injected) {
            if (!$injected && str_starts_with(strtolower($query), 'insert into `comentarios_ticket`')) {
                $injected = true;
                throw new \RuntimeException('Fallo simulado al guardar el mensaje.');
            }
        });
        try {
            $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), ['comment' => 'No debe quedar guardado a medias.']);
            $this->fail('Debió fallar la escritura simulada.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo simulado al guardar el mensaje.', $e->getMessage());
        }
        $this->assertTrue($injected);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_derivar_no_habilita_a_usuarios_comunes_del_departamento_y_el_soporte_debe_tomarlo(): void
    {
        $ticket = $this->ticket();
        $destination = Department::create(['name' => 'Otro departamento', 'is_active' => true]);
        $ordinary = User::factory()->create(['department_id' => $destination->id]);
        $next = User::factory()->soporte()->create(['department_id' => $destination->id]);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.forward', $ticket), ['department_id' => $destination->id])->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->assigned_to);
        $this->actingAs($ordinary)->get(route('tickets.show', $ticket))->assertForbidden();
        $this->post(route('tickets.selfAssign', $ticket))->assertForbidden();
        $this->post(route('tickets.addComment', $ticket), ['comment' => 'No debo intervenir.'])->assertForbidden();
        $this->actingAs($next)->post(route('tickets.addComment', $ticket), ['comment' => 'Primero debo tomarlo.'])->assertForbidden();
        $this->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->post(route('tickets.addComment', $ticket), ['comment' => 'Ahora soy responsable del caso.'])->assertSessionHas('success');
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
        $this->assertSame(1, $ticket->comments()->count());
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
    }
}
