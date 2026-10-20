<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Notificacion;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketDelegation;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketUpdatedNotification;
use App\Support\TicketDelegationNotifier;
use App\Support\TicketNotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketDelegationNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ticket_delegation.enabled' => true]);
        Mail::fake();
        Notification::fake();
        $this->travelTo(now()->startOfSecond());
    }

    /** Todos son datos ficticios y el encargado conserva el rol user. */
    private function scenario(): array
    {
        $origin = Department::create(['name' => 'Origen '.fake()->unique()->numerify('######'), 'is_active' => true]);
        $destination = Department::create(['name' => 'Destino '.fake()->unique()->numerify('######'), 'is_active' => true]);
        $owner = User::factory()->create(['department_id' => $origin->id]);
        $responsible = User::factory()->soporte()->create(['department_id' => $origin->id]);
        $delegate = User::factory()->create(['department_id' => $destination->id]);
        $other = User::factory()->create(['department_id' => $destination->id]);
        $admin = User::factory()->administrador()->create(['department_id' => $origin->id]);
        $ticket = Ticket::create([
            'ticket_number' => 'DELEG-NOTIFY-'.fake()->unique()->numerify('##########'),
            'user_id' => $owner->id, 'assigned_to' => $responsible->id, 'department_id' => $origin->id,
            'title' => 'Caso puntual', 'description' => 'Datos de la solicitud.', 'status' => 'in_progress', 'priority' => 'medium',
        ])->fresh();
        $delegation = TicketDelegation::create([
            'ticket_id' => $ticket->id, 'delegate_id' => $delegate->id, 'responsible_id' => $responsible->id,
            'delegated_by' => $responsible->id, 'department_id' => $destination->id,
            'instructions' => 'Instrucciones privadas de este encargo.', 'status' => 'active', 'version' => 1, 'reopened_count' => 0,
        ])->fresh();
        return compact('owner', 'responsible', 'delegate', 'other', 'admin', 'ticket', 'delegation', 'origin', 'destination');
    }

    private function disabled(string $key, string $group = 'notifications'): void
    {
        SystemSetting::create(['key' => $key, 'value' => '0', 'type' => 'boolean', 'group' => $group]);
        SystemSetting::forgetCached($key);
    }

    public function test_encargado_normal_no_recibe_avisos_globales_de_tickets_nuevos(): void
    {
        extract($this->scenario());
        $before = $delegate->fresh()->getAttributes();
        $this->actingAs($owner)->post(route('tickets.store'), ['title' => 'Otro caso sin relación con el encargo'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertNotSentTo($delegate, NewTicketNotification::class);
        Notification::assertNotSentTo($other, NewTicketNotification::class);
        Notification::assertSentTo($responsible, NewTicketNotification::class);
        Notification::assertSentTo($admin, NewTicketNotification::class);
        $this->assertDatabaseMissing('notificaciones', ['user_id' => $delegate->id, 'type' => 'new_ticket']);
        $this->assertSame($before, $delegate->fresh()->getAttributes());
        $this->assertSame('user', $delegate->fresh()->role);
    }

    public function test_aviso_del_encargo_se_dirige_solo_a_la_persona_y_no_incluye_propuesta_privada(): void
    {
        extract($this->scenario());
        $delegation->update(['proposal_text' => 'Propuesta privada que no debe viajar en avisos.']);
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_assigned', 'Tienes un encargo para revisar desde el ticket.');
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 1);
        Notification::assertCount(1);
        $notice = Notificacion::sole();
        $this->assertSame($delegate->id, $notice->user_id);
        $this->assertSame($ticket->id, $notice->ticket_id);
        $this->assertStringNotContainsString($delegation->proposal_text, $notice->body);
        $this->assertStringNotContainsString($delegation->instructions, $notice->body);
        Notification::assertSentTo($delegate, TicketUpdatedNotification::class, function ($notification) use ($delegate, $delegation) {
            $mail = $notification->toMail($delegate);
            $lines = implode(' ', $mail->introLines);
            $this->assertStringNotContainsString($delegation->proposal_text, $lines);
            $this->assertStringNotContainsString($delegation->instructions, $lines);
            return true;
        });
        $this->assertSame('user', $delegate->fresh()->role);
    }

    public function test_propuesta_notifica_solo_al_responsable_e_informa_ajustes_solo_al_encargado(): void
    {
        extract($this->scenario());
        TicketDelegationNotifier::send($responsible, $ticket, 'delegation_proposed', 'Hay una solución propuesta pendiente de revisión.');
        Notification::assertSentToTimes($responsible, TicketUpdatedNotification::class, 1);
        Notification::assertNothingSentTo($owner);
        Notification::assertNothingSentTo($delegate);
        Notification::assertNothingSentTo($admin);
        Notification::assertNothingSentTo($other);
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_changes', 'El responsable solicitó ajustes. Revisa el encargo.');
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 1);
        Notification::assertCount(2);
        $this->assertDatabaseCount('notificaciones', 2);
    }

    public static function publicReplies(): array
    {
        return ['respuesta' => [false, 'comment'], 'solicitud de información' => [true, 'info_requested']];
    }

    #[DataProvider('publicReplies')]
    public function test_respuesta_publica_notifica_al_solicitante_y_al_encargado_actual_no_a_todos(bool $requestInfo, string $event): void
    {
        extract($this->scenario());
        $this->actingAs($responsible)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Confirme el resultado para continuar.', 'request_info' => $requestInfo ? 1 : 0,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertSentToTimes($owner, TicketUpdatedNotification::class, 1);
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 1);
        Notification::assertNothingSentTo($other);
        Notification::assertNothingSentTo($admin);
        Notification::assertCount(2);
        $this->assertDatabaseHas('notificaciones', ['user_id' => $delegate->id, 'ticket_id' => $ticket->id, 'type' => $event]);
        $this->assertSame($responsible->id, $ticket->fresh()->assigned_to);
        $this->assertSame('user', $delegate->fresh()->role);
    }

    public function test_respuesta_del_invitado_avisa_al_responsable_y_encargado_sin_requerir_cuenta_al_solicitante(): void
    {
        extract($this->scenario());
        $ticket->update(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'delegation-guest@example.test',
            'guest_token' => str_repeat('j', 40), 'status' => 'pending_user']);
        Auth::logout();
        $this->post(route('tickets.guest.comment', $ticket->guest_token), ['comment' => 'Envío el resultado solicitado.'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertSentToTimes($responsible, TicketUpdatedNotification::class, 1);
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 1);
        Notification::assertNothingSentTo($other);
        Notification::assertNothingSentTo($admin);
        Notification::assertCount(2);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
    }

    public function test_nota_interna_no_genera_avisos_para_el_encargado_ni_el_solicitante(): void
    {
        extract($this->scenario());
        $this->actingAs($responsible)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Nota interna que solo corresponde al personal de soporte.', 'is_internal' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_aviso_automatico_revisa_al_encargado_actual_no_al_anterior_ni_al_excluido(): void
    {
        extract($this->scenario());
        TicketDelegationNotifier::notifyDelegate($ticket, 'comment', 'Mensaje público nuevo.', $delegate->id);
        Notification::assertNothingSent();
        $delegation->update(['status' => 'revoked', 'version' => 2]);
        TicketDelegation::create([
            'ticket_id' => $ticket->id, 'delegate_id' => $other->id, 'responsible_id' => $responsible->id,
            'delegated_by' => $responsible->id, 'department_id' => $destination->id,
            'instructions' => 'Nuevo encargo.', 'status' => 'active', 'version' => 1, 'reopened_count' => 0,
        ]);
        TicketDelegationNotifier::notifyDelegate($ticket, 'comment', 'Mensaje público nuevo.');
        Notification::assertNothingSentTo($delegate);
        Notification::assertSentToTimes($other, TicketUpdatedNotification::class, 1);
        Notification::assertCount(1);
    }

    public function test_revocado_no_recibe_actividad_futura_pero_puede_recibir_su_aviso_generico_de_retiro(): void
    {
        extract($this->scenario());
        $delegation->update(['status' => 'revoked', 'version' => 2]);
        TicketDelegationNotifier::notifyDelegate($ticket, 'comment', 'No debe llegar al revocado.');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('notificaciones', 0);
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_revoked', 'El encargo ya no está vigente.');
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 1);
        $this->assertSame('El encargo ya no está vigente.', Notificacion::sole()->body);
    }

    public function test_encargo_aprobado_conserva_aviso_de_resolucion_y_cierre_sin_habilitar_avisos_globales(): void
    {
        extract($this->scenario());
        $delegation->update(['status' => 'approved', 'version' => 2]);
        $ticket->update(['status' => 'resolved', 'resolved_at' => now(), 'solution_text' => 'Solución revisada por el responsable.']);
        TicketDelegationNotifier::notifyDelegate($ticket, 'resolved', 'La solución fue validada.');
        $ticket->update(['status' => 'closed', 'closed_at' => now()]);
        TicketDelegationNotifier::notifyDelegate($ticket, 'closed', 'El caso fue cerrado.');
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 2);
        Notification::assertNothingSentTo($other);
        Notification::assertCount(2);
        $this->assertSame(['resolved', 'closed'], Notificacion::orderBy('id')->pluck('type')->all());
    }

    public function test_funcion_desactivada_no_produce_avisos_del_encargado(): void
    {
        extract($this->scenario());
        config(['ticket_delegation.enabled' => false]);
        TicketDelegationNotifier::notifyDelegate($ticket, 'comment', 'No debe notificarse con la función apagada.');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_cuenta_desactivada_no_recibe_encargo_ni_actividad_del_caso(): void
    {
        extract($this->scenario());
        $delegate->update(['is_active' => false]);
        TicketDelegationNotifier::send($delegate->fresh(), $ticket, 'delegation_assigned', 'No debe llegar.');
        TicketDelegationNotifier::notifyDelegate($ticket, 'comment', 'Tampoco debe llegar.');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_modelo_antiguo_no_habilita_avisos_si_la_cuenta_ya_fue_desactivada(): void
    {
        extract($this->scenario());
        $before = $delegation->fresh()->getAttributes();
        DB::table('usuarios')->where('id', $delegate->id)->update(['is_active' => false]);
        $this->assertTrue($delegate->is_active);
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_assigned', 'No debe enviarse por un modelo desactualizado.');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertSame($before, $delegation->fresh()->getAttributes());
    }

    public static function delegationEvents(): array
    {
        return [
            'encargo' => ['delegation_assigned', 'notify_on_assignment'],
            'propuesta' => ['delegation_proposed', 'notify_on_comment'],
            'ajustes' => ['delegation_changes', 'notify_on_comment'],
            'retiro' => ['delegation_revoked', 'notify_on_assignment'],
            'aprobación' => ['delegation_approved', 'notify_on_status_change'],
        ];
    }

    #[DataProvider('delegationEvents')]
    public function test_eventos_del_encargo_respetan_interruptores_existentes_sin_agregar_opciones(string $event, string $key): void
    {
        extract($this->scenario());
        $this->disabled($key);
        $this->assertSame($key, TicketNotificationPreferences::EVENT_KEYS[$event]);
        TicketDelegationNotifier::send($delegate, $ticket, $event, 'Aviso desactivado por el administrador.');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertDatabaseCount('system_settings', 1);
    }

    public function test_preferencia_personal_desactiva_solo_correo_y_conserva_campana_y_alternativo(): void
    {
        extract($this->scenario());
        $delegate->update(['alternate_email' => 'delegate-alternate@example.test']);
        $this->disabled(TicketNotificationPreferences::emailKey($delegate->id), 'user_preferences');
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_assigned', 'Tienes un encargo para revisar.');
        Notification::assertNothingSent();
        $this->assertSame($delegate->id, Notificacion::sole()->user_id);
        $this->assertSame([$delegate->email, $delegate->alternate_email], $delegate->routeNotificationForMail(null));
    }

    public function test_fallo_del_correo_no_deshace_campana_ni_encargo(): void
    {
        extract($this->scenario());
        $before = $delegation->fresh()->getAttributes();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Correo simulado no disponible.'));
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_assigned', 'Tienes un encargo.');
        $this->assertSame($delegate->id, Notificacion::sole()->user_id);
        $this->assertSame($before, $delegation->fresh()->getAttributes());
    }

    public function test_fallo_de_campana_no_impide_el_intento_de_correo_ni_modifica_el_encargo(): void
    {
        extract($this->scenario());
        $before = $delegation->fresh()->getAttributes();
        $failed = false;
        DB::connection()->beforeExecuting(function ($query) use (&$failed) {
            if (!$failed && str_starts_with(strtolower($query), 'insert into `notificaciones`')) {
                $failed = true;
                throw new \RuntimeException('Campana simulada no disponible.');
            }
        });
        TicketDelegationNotifier::send($delegate, $ticket, 'delegation_assigned', 'Tienes un encargo.');
        $this->assertTrue($failed);
        Notification::assertSentToTimes($delegate, TicketUpdatedNotification::class, 1);
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertSame($before, $delegation->fresh()->getAttributes());
    }
}
