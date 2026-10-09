<?php

namespace Tests\Feature;

use App\Jobs\AutoCloseTicketJob;
use App\Jobs\CloseResolvedTicketsJob;
use App\Mail\GuestTicketStatusMail;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TicketResolutionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();
        $this->travelTo(now()->startOfSecond());
        RateLimiter::clear('guest_reopen:127.0.0.1');
        RateLimiter::clear('guest_comment:127.0.0.1');
    }

    private function ticket(array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'RES-'.fake()->unique()->numerify('##########'),
            'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->soporte()->create()->id,
            'title' => 'Falla del correo', 'description' => 'Descripción que se conserva.',
            'status' => Ticket::STATUS_IN_PROGRESS, 'priority' => 'medium',
        ], $extra));
    }

    private function resolve(Ticket $ticket): void
    {
        $this->actingAs($ticket->assignedTo)->post(route('tickets.resolve', $ticket), [
            'solution_text' => 'Se corrigió la configuración y se probó el envío de correo.',
        ])->assertRedirect()->assertSessionHas('success');
        $ticket->refresh();
    }

    private function forms(string $html, string $suffix): int
    {
        $document = new \DOMDocument();
        @$document->loadHTML($html);
        $count = 0;
        foreach ((new \DOMXPath($document))->query('//form') as $form) {
            if (str_ends_with($form->getAttribute('action'), $suffix)) $count++;
        }
        return $count;
    }

    public function test_el_asignado_registra_solucion_con_fecha_historial_aviso_y_sin_cerrar(): void
    {
        $ticket = $this->ticket(['status' => Ticket::STATUS_PENDING_USER, 'response_deadline_at' => now()->subHour()]);
        $this->resolve($ticket);
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->status);
        $this->assertTrue($ticket->resolved_at->equalTo(now()));
        $this->assertNull($ticket->closed_at);
        $this->assertNull($ticket->response_deadline_at);
        $this->assertSame('Descripción que se conserva.', $ticket->description);
        $this->assertDatabaseHas('historial_ticket', ['ticket_id' => $ticket->id, 'action' => 'solution_registered', 'old_value' => Ticket::STATUS_PENDING_USER]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.resolved', 'model_id' => $ticket->id]);
        $this->assertDatabaseHas('notificaciones', ['user_id' => $ticket->user_id, 'type' => 'resolved']);
        (new AutoCloseTicketJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
    }

    public function test_otro_soporte_y_el_solicitante_no_pueden_registrar_la_solucion(): void
    {
        $ticket = $this->ticket();
        $before = $ticket->fresh()->getAttributes();
        foreach ([$ticket->user, User::factory()->soporte()->create()] as $actor) {
            $this->actingAs($actor)->post(route('tickets.resolve', $ticket), ['solution_text' => 'No debe registrarse esta solución.'])->assertForbidden();
            $this->assertSame($before, $ticket->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_admin_conserva_supervision_pero_no_resuelve_sin_responsable(): void
    {
        $admin = User::factory()->administrador()->create();
        $ticket = $this->ticket(['assigned_to' => null]);
        $this->actingAs($admin)->post(route('tickets.resolve', $ticket), ['solution_text' => 'Solución sin agente no permitida.'])->assertSessionHas('error');
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $ticket->update(['assigned_to' => User::factory()->soporte()->create()->id]);
        $this->actingAs($admin)->post(route('tickets.resolve', $ticket), ['solution_text' => 'Solución registrada bajo supervisión.'])->assertSessionHas('success');
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
    }

    public function test_la_solucion_es_obligatoria_y_no_basta_con_un_texto_minimo(): void
    {
        $ticket = $this->ticket();
        foreach (['', 'listo'] as $solution) {
            $this->actingAs($ticket->assignedTo)->post(route('tickets.resolve', $ticket), ['solution_text' => $solution])->assertSessionHasErrors('solution_text');
        }
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_repetir_resolver_no_duplica_registros_ni_reinicia_la_hora(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $before = $ticket->fresh()->getAttributes();
        $this->travel(30)->minutes();
        $this->actingAs($ticket->assignedTo)->post(route('tickets.resolve', $ticket), ['solution_text' => 'Intento de reiniciar el tiempo.'])->assertSessionHas('error');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('historial_ticket', 1);
        $this->assertDatabaseCount('notificaciones', 1);
    }

    public function test_no_cierra_antes_de_una_hora_y_si_cierra_al_cumplirse(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $resolvedAt = $ticket->resolved_at->copy();
        $this->travel(3599)->seconds();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->travel(1)->seconds();
        (new CloseResolvedTicketsJob)->handle();
        $ticket->refresh();
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->status);
        $this->assertTrue($ticket->resolved_at->equalTo($resolvedAt));
        $this->assertTrue($ticket->closed_at->equalTo(now()));
        $this->assertNotEmpty($ticket->solution_text);
        $this->assertDatabaseHas('historial_ticket', ['action' => 'auto_closed_resolved', 'old_value' => Ticket::STATUS_RESOLVED, 'user_id' => null]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.auto_closed_resolved']);
        $this->assertDatabaseHas('notificaciones', ['user_id' => $ticket->user_id, 'type' => 'closed']);
    }

    public function test_la_hora_es_de_reloj_incluso_fuera_de_horario_laboral(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-10 23:30:00'));
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $this->travel(1)->hours();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
    }

    public function test_reabrir_antes_del_cierre_anula_el_plazo_y_registrar_otra_solucion_inicia_otra_hora(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $this->travel(30)->minutes();
        $this->actingAs($ticket->user)->post(route('tickets.reopen', $ticket), ['motivo' => 'El correo sigue fallando al enviar.'])->assertSessionHas('success');
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->travel(31)->minutes();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->resolve($ticket);
        $this->travel(59)->minutes();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->travel(1)->minutes();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
    }

    public function test_un_invitado_recibe_solucion_y_puede_reabrir_por_su_enlace_sin_login(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest@example.test', 'guest_token' => str_repeat('r', 40)]);
        $this->resolve($ticket);
        Mail::assertQueued(GuestTicketStatusMail::class, fn ($mail) => $mail->hasTo('guest@example.test') && str_contains($mail->statusMessage, 'una hora'));
        Auth::logout();
        $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()->assertSee($ticket->solution_text)->assertSee('Cierre automático a partir del')->assertSee('Reabrir el ticket');
        $this->post(route('tickets.guest.reopen', $ticket->guest_token), ['motivo' => 'La solución no corrigió el problema.'])->assertSessionHas('success');
        $this->travel(2)->hours();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    public function test_el_invitado_recibe_un_correo_de_cierre_por_solucion_y_no_por_falta_de_respuesta(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest@example.test', 'guest_token' => str_repeat('s', 40)]);
        $this->resolve($ticket);
        $this->travel(1)->hours();
        (new CloseResolvedTicketsJob)->handle();
        Mail::assertQueued(GuestTicketStatusMail::class, fn ($mail) => str_contains($mail->statusMessage, 'se cerró automáticamente') && !str_contains($mail->statusMessage, 'no recibimos'));
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
    }

    public function test_ningun_ticket_historico_se_cierra_sin_marca_del_nuevo_flujo(): void
    {
        $legacy = $this->ticket(['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now()->subDays(10), 'solution_text' => 'Solución antigua que se conserva.']);
        $noDate = $this->ticket(['status' => Ticket::STATUS_RESOLVED]);
        $active = $this->ticket();
        $before = $legacy->fresh()->getAttributes();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame($before, $legacy->fresh()->getAttributes());
        $this->assertSame(Ticket::STATUS_RESOLVED, $noDate->fresh()->status);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $active->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_tres_pasadas_del_job_no_duplican_cierre_historial_ni_avisos(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $this->travel(1)->hours();
        for ($i = 0; $i < 3; $i++) (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(1, TicketHistory::where('action', 'auto_closed_resolved')->count());
        $this->assertSame(1, AuditLog::where('action', 'ticket.auto_closed_resolved')->count());
        $this->assertSame(1, Notificacion::where('type', 'closed')->count());
    }

    public function test_no_hay_selector_manual_y_solo_el_asignado_y_admin_ven_registrar_solucion(): void
    {
        $ticket = $this->ticket();
        foreach ([$ticket->user, User::factory()->soporte()->create(), $ticket->assignedTo, User::factory()->administrador()->create()] as $actor) {
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $response = $this->actingAs($actor)->get(route($route, $ticket))->assertOk();
                $this->assertSame(0, $this->forms($response->getContent(), '/status'));
                $this->assertSame($actor->isAdmin() || $actor->id === $ticket->assigned_to ? 1 : 0, $this->forms($response->getContent(), '/resolve'));
            }
        }
    }

    public function test_la_url_manual_no_cambia_ningun_estado_incluso_si_la_llama_admin(): void
    {
        foreach ([Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED] as $status) {
            $ticket = $this->ticket(['status' => $status]);
            $before = $ticket->fresh()->getAttributes();
            foreach ([$ticket->assignedTo, User::factory()->administrador()->create()] as $actor) {
                foreach ([Ticket::STATUS_OPEN, Ticket::STATUS_PENDING_USER, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED] as $target) {
                    $this->actingAs($actor)->put(route('tickets.updateStatus', $ticket), ['status' => $target])->assertSessionHas('error');
                    $this->assertSame($before, $ticket->fresh()->getAttributes());
                }
            }
        }
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_un_cerrado_no_se_resuelve_se_reabre_o_se_vuelve_a_cerrar_por_ningun_rol(): void
    {
        $ticket = $this->ticket(['status' => Ticket::STATUS_CLOSED, 'closed_at' => now(), 'solution_text' => 'Solución definitiva que se conserva.']);
        $before = $ticket->fresh()->getAttributes();
        foreach ([$ticket->user, $ticket->assignedTo, User::factory()->administrador()->create()] as $actor) {
            $this->actingAs($actor)->post(route('tickets.close', $ticket), ['solution_text' => 'Intento de sobrescribir la solución.'])->assertSessionHas('error');
            $this->actingAs($actor)->post(route('tickets.reopen', $ticket), ['motivo' => 'Intento de reabrir un ticket cerrado.'])->assertSessionHas('error');
            if (!$actor->isUser()) $this->actingAs($actor)->post(route('tickets.resolve', $ticket), ['solution_text' => 'Otra solución no debe guardarse.'])->assertSessionHas('error');
            $this->assertSame($before, $ticket->fresh()->getAttributes());
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $html = $this->get(route($route, $ticket))->assertOk()->getContent();
                foreach (['/status', '/resolve', '/close', '/reopen'] as $suffix) $this->assertSame(0, $this->forms($html, $suffix));
            }
        }
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_resuelto_no_admite_comentarios_ni_solicitud_de_informacion_ni_derivacion(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        foreach ([$ticket->user, $ticket->assignedTo, User::factory()->administrador()->create()] as $actor) {
            $this->actingAs($actor)->post(route('tickets.addComment', $ticket), ['comment' => 'No debe cambiar el estado.', 'request_info' => 1])->assertSessionHasErrors('comment');
            $html = $this->get(route('tickets.show', $ticket))->assertOk()->getContent();
            $this->assertSame(0, $this->forms($html, '/comment'));
        }
        $this->actingAs($ticket->assignedTo)->post(route('tickets.forward', $ticket), ['department_id' => 999])->assertSessionHas('error');
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
    }

    public function test_confirmar_antes_de_la_hora_conserva_la_solucion_del_soporte(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $solution = $ticket->solution_text;
        $this->actingAs($ticket->user)->post(route('tickets.close', $ticket), ['solution_text' => 'Texto manipulado que no debe reemplazar la solución.'])->assertSessionHas('success');
        $this->assertSame($solution, $ticket->fresh()->solution_text);
        $this->travel(1)->hours();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(0, TicketHistory::where('action', 'auto_closed_resolved')->count());
    }

    public function test_admin_no_puede_saltar_registro_de_solucion_y_cerrar_un_ticket_activo(): void
    {
        $ticket = $this->ticket();
        $this->actingAs(User::factory()->administrador()->create())->post(route('tickets.close', $ticket), ['solution_text' => 'Solución que debe registrarse primero.'])->assertSessionHas('error');
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    private function changeBeforeNextUpdate(Ticket $ticket, array $attributes): void
    {
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings, $connection) use (&$injected, $ticket, $attributes) {
            if (!$injected && str_starts_with(strtolower($query), 'update `tickets`')) {
                $injected = true;
                $connection->table('tickets')->where('id', $ticket->id)->update($attributes);
            }
        });
    }

    public function test_resolucion_pendiente_no_pisa_un_cierre_que_gana_la_carrera(): void
    {
        $ticket = $this->ticket();
        $this->changeBeforeNextUpdate($ticket, ['status' => Ticket::STATUS_CLOSED]);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.resolve', $ticket), ['solution_text' => 'No debe reactivar un ticket cerrado.'])->assertSessionHas('error');
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_resolucion_pendiente_no_pisa_una_reasignacion(): void
    {
        $ticket = $this->ticket();
        $winner = User::factory()->soporte()->create();
        $this->changeBeforeNextUpdate($ticket, ['assigned_to' => $winner->id]);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.resolve', $ticket), ['solution_text' => 'No debe resolver el agente anterior.'])->assertSessionHas('error');
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_reapertura_pendiente_no_revive_un_cierre_que_gana_la_carrera(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $this->changeBeforeNextUpdate($ticket, ['status' => Ticket::STATUS_CLOSED]);
        $this->actingAs($ticket->user)->post(route('tickets.reopen', $ticket), ['motivo' => 'No debe reabrir si acaba de cerrarse.'])->assertSessionHas('error');
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertSame(0, TicketHistory::where('action', 'reopened')->count());
    }

    public function test_job_pendiente_no_cierra_un_ticket_reabierto_mientras_trabajaba(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $this->travel(1)->hours();
        $this->changeBeforeNextUpdate($ticket, ['status' => Ticket::STATUS_IN_PROGRESS, 'resolved_at' => null]);
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame(0, TicketHistory::where('action', 'auto_closed_resolved')->count());
    }

    public function test_job_pendiente_no_cierra_una_nueva_solucion_con_otra_fecha(): void
    {
        $ticket = $this->ticket();
        $this->resolve($ticket);
        $this->travel(1)->hours();
        $this->changeBeforeNextUpdate($ticket, ['resolved_at' => now(), 'solution_text' => 'Nueva solución con nueva hora.']);
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->assertSame(0, TicketHistory::where('action', 'auto_closed_resolved')->count());
    }

    public function test_el_cierre_antiguo_por_falta_de_respuesta_no_pisa_una_resolucion(): void
    {
        $ticket = $this->ticket(['status' => Ticket::STATUS_PENDING_USER, 'response_deadline_at' => now()->subHour()]);
        $this->changeBeforeNextUpdate($ticket, ['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now()]);
        (new AutoCloseTicketJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->assertSame(0, TicketHistory::where('action', 'auto_closed')->count());
    }

    public function test_responder_pendiente_no_reabre_un_cerrado_ni_guarda_un_mensaje_fallido(): void
    {
        $ticket = $this->ticket(['status' => Ticket::STATUS_PENDING_USER]);
        $this->changeBeforeNextUpdate($ticket, ['status' => Ticket::STATUS_CLOSED]);
        $this->actingAs($ticket->user)->post(route('tickets.addComment', $ticket), ['comment' => 'Respuesta en una pestaña antigua.'])->assertSessionHasErrors('comment');
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
    }

    public function test_fallo_de_correo_no_deshace_solucion_ni_cierre(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest@example.test', 'guest_token' => str_repeat('t', 40)]);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Correo no disponible'));
        $this->resolve($ticket);
        $this->travel(1)->hours();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ticket.auto_closed_resolved']);
    }

    public function test_solucion_y_correo_muestran_html_como_texto_no_como_codigo(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest@example.test', 'guest_token' => str_repeat('u', 40)]);
        $solution = '<img src=x onerror="window.test=1"> solución aplicada';
        $this->actingAs($ticket->assignedTo)->post(route('tickets.resolve', $ticket), ['solution_text' => $solution])->assertSessionHas('success');
        Auth::logout();
        $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()->assertSee($solution)->assertDontSee($solution, false);
        $html = (new GuestTicketStatusMail($ticket->fresh(), 'Aviso de solución.'))->render();
        $this->assertStringNotContainsString($solution, $html);
        $this->assertStringContainsString(e($solution), $html);
        $this->assertStringContainsString(route('tickets.guest.show', $ticket->guest_token), $html);
    }
}
