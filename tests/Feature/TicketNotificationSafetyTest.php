<?php

namespace Tests\Feature;

use App\Mail\GuestTicketStatusMail;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use App\Support\TicketNotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketNotificationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
    }

    private function ticket(bool $guest = false, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'SAFE-'.fake()->unique()->numerify('############'),
            'user_id' => $guest ? null : User::factory()->create()->id,
            'assigned_to' => User::factory()->soporte()->create()->id,
            'status' => Ticket::STATUS_IN_PROGRESS, 'priority' => 'medium',
            'title' => 'Conservar la conversación', 'description' => 'Descripción existente.',
            'guest_name' => $guest ? 'Invitado' : null,
            'guest_email' => $guest ? 'guest-safety@example.test' : null,
            'guest_token' => $guest ? fake()->unique()->regexify('[a-z]{40}') : null,
        ], $extra))->fresh();
    }

    private function disable(string $key): void
    {
        SystemSetting::create(['key' => $key, 'value' => '0', 'type' => 'boolean', 'group' => 'notifications']);
        SystemSetting::forgetCached($key);
    }

    public function test_una_nota_interna_no_envia_ningun_aviso_externo_ni_cambia_el_ticket(): void
    {
        foreach ([false, true] as $guest) {
            $ticket = $this->ticket($guest);
            $before = $ticket->fresh()->getAttributes();
            $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
                'comment' => 'Detalle privado que no se debe avisar al solicitante.',
                'is_internal' => 1,
                'attachments' => [UploadedFile::fake()->createWithContent('privado.txt', 'Adjunto confidencial')],
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame($before, $ticket->fresh()->getAttributes());
            $this->assertSame(1, $ticket->comments()->where('is_internal', true)->count());
            $this->assertSame(1, $ticket->attachments()->count());
        }
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
        $this->assertDatabaseCount('notificaciones', 0);
    }

    public function test_un_fallo_de_correo_no_convierte_un_comentario_guardado_en_error(): void
    {
        $ticket = $this->ticket();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP de prueba no disponible'));
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Revise nuevamente y confirme el resultado.',
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(1, $ticket->comments()->count());
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
        $this->assertDatabaseHas('notificaciones', ['user_id' => $ticket->user_id, 'ticket_id' => $ticket->id, 'type' => 'comment']);
    }

    public function test_fallo_de_correo_al_responder_el_solicitante_tampoco_desincroniza_el_turno(): void
    {
        $ticket = $this->ticket(false, ['status' => Ticket::STATUS_PENDING_USER, 'response_deadline_at' => now()->addHours(2)]);
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP de prueba no disponible'));
        $this->actingAs($ticket->user)->post(route('tickets.addComment', $ticket), ['comment' => 'Envío la información que me solicitaron.'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(1, $ticket->comments()->count());
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->response_deadline_at);
        $this->assertNotNull($ticket->fresh()->user_responded_at);
    }

    public static function requesterEvents(): array
    {
        return ['respuesta pública' => [false, 'comment'], 'solicitud explícita' => [true, 'info_requested']];
    }

    #[DataProvider('requesterEvents')]
    public function test_el_invitado_recibe_un_solo_aviso_con_su_enlace_privado(bool $requestInfo, string $event): void
    {
        $ticket = $this->ticket(true);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Confirme si ya puede acceder a su cuenta.', 'request_info' => $requestInfo ? 1 : 0,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        Mail::assertQueued(GuestTicketStatusMail::class, function ($mail) use ($ticket) {
            $this->assertStringContainsString(route('tickets.guest.show', $ticket->guest_token), $mail->render());
            return $mail->hasTo($ticket->guest_email) && $mail->ticket->id === $ticket->id;
        });
        Mail::assertQueuedCount(1);
        Notification::assertNothingSent();
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
        $this->assertSame($requestInfo, $ticket->fresh()->response_deadline_at !== null);
    }

    #[DataProvider('requesterEvents')]
    public function test_los_avisos_nuevos_del_invitado_respetan_el_interruptor_global(bool $requestInfo, string $event): void
    {
        $this->disable(TicketNotificationPreferences::EVENT_KEYS[$event]);
        $ticket = $this->ticket(true);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Esta respuesta debe guardarse aunque el correo esté desactivado.', 'request_info' => $requestInfo ? 1 : 0,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
        $this->assertSame(1, $ticket->comments()->count());
    }

    public function test_fallo_de_aviso_al_invitado_no_produce_falso_error_de_guardado(): void
    {
        $ticket = $this->ticket(true);
        Mail::shouldReceive('to')->once()->with($ticket->guest_email)->andThrow(new \RuntimeException('Cola de prueba no disponible'));
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Necesitamos una captura del mensaje.', 'request_info' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(1, $ticket->comments()->count());
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->response_deadline_at);
    }

    public function test_solicitar_informacion_a_un_registrado_conserva_campana_y_envia_correo(): void
    {
        $ticket = $this->ticket();
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Necesitamos una captura del error para continuar.', 'request_info' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertSentToTimes($ticket->user, TicketUpdatedNotification::class, 1);
        Notification::assertSentTo($ticket->user, TicketUpdatedNotification::class, fn ($notice) => $notice->via($ticket->user) === ['mail']);
        $this->assertDatabaseHas('notificaciones', ['user_id' => $ticket->user_id, 'ticket_id' => $ticket->id, 'type' => 'info_requested']);
        $this->assertNotNull($ticket->fresh()->response_deadline_at);
    }

    public function test_preferencia_personal_desactiva_correo_de_solicitud_pero_no_su_campana(): void
    {
        $ticket = $this->ticket();
        $this->disable(TicketNotificationPreferences::emailKey($ticket->user_id));
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Necesitamos conocer el resultado.', 'request_info' => 1,
        ])->assertSessionHasNoErrors();
        Notification::assertNothingSent();
        $this->assertDatabaseHas('notificaciones', ['user_id' => $ticket->user_id, 'ticket_id' => $ticket->id, 'type' => 'info_requested']);
    }

    public function test_transferencia_envia_una_notificacion_por_persona_y_conserva_sus_dos_direcciones(): void
    {
        $ticket = $this->ticket();
        $next = User::factory()->soporte()->create(['alternate_email' => 'alternativo-safety@example.test']);
        $this->actingAs($ticket->assignedTo)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        Notification::assertSentToTimes($next, TicketUpdatedNotification::class, 1);
        Notification::assertSentToTimes($ticket->user, TicketUpdatedNotification::class, 1);
        Notification::assertCount(2);
        $this->assertSame([$next->email, $next->alternate_email], $next->routeNotificationForMail(null));
        $this->assertDatabaseHas('notificaciones', ['user_id' => $next->id, 'ticket_id' => $ticket->id, 'type' => 'assigned']);
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
    }

    public function test_un_destinatario_con_error_no_impide_avisar_al_otro_ni_guardar_la_transferencia(): void
    {
        $ticket = $this->ticket();
        $next = User::factory()->soporte()->create();
        $attempted = [];
        Notification::shouldReceive('send')->twice()->andReturnUsing(function ($recipient) use (&$attempted, $ticket) {
            $attempted[] = $recipient->id;
            if ($recipient->id === $ticket->user_id) throw new \RuntimeException('Primer destinatario falló');
        });
        $this->actingAs($ticket->assignedTo)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame([$ticket->user_id, $next->id], $attempted);
        $this->assertSame($next->id, $ticket->fresh()->assigned_to);
        $this->assertSame(1, $ticket->history()->where('action', 'assigned')->count());
    }
}
