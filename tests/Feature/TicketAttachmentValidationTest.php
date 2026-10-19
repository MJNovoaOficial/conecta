<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketAttachmentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        Storage::fake('local');
    }

    private function ticket(bool $guest): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'FILES-'.fake()->unique()->numerify('############'),
            'user_id' => $guest ? null : User::factory()->create()->id,
            'assigned_to' => User::factory()->soporte()->create()->id,
            'status' => Ticket::STATUS_PENDING_USER, 'priority' => 'medium',
            'last_response_request_at' => now(), 'response_deadline_at' => now()->addHours(2),
            'title' => 'Ticket existente', 'description' => 'Conservar los datos anteriores.',
            'guest_name' => $guest ? 'Invitado' : null,
            'guest_email' => $guest ? 'attachment-safety@example.test' : null,
            'guest_token' => $guest ? str_repeat('v', 40) : null,
        ])->fresh();
    }

    public static function entryPoints(): array
    {
        return [
            'alta registrada: extensión inválida' => [false, false, 'captura.heic', 'image/heic'],
            'alta invitado: extensión inválida' => [true, false, 'captura.heic', 'image/heic'],
            'respuesta registrada: extensión inválida' => [false, true, 'captura.heic', 'image/heic'],
            'respuesta invitado: extensión inválida' => [true, true, 'captura.heic', 'image/heic'],
            'alta registrada: MIME inválido' => [false, false, 'captura.jpg', 'application/x-php'],
            'alta invitado: MIME inválido' => [true, false, 'captura.jpg', 'application/x-php'],
            'respuesta registrada: MIME inválido' => [false, true, 'captura.jpg', 'application/x-php'],
            'respuesta invitado: MIME inválido' => [true, true, 'captura.jpg', 'application/x-php'],
        ];
    }

    #[DataProvider('entryPoints')]
    public function test_rechazar_archivo_no_admitido_no_guarda_parcialmente_y_permite_corregir_el_envio(bool $guest, bool $reply, string $filename, string $mime): void
    {
        $existing = $this->ticket($guest);
        $comment = $existing->comments()->create(['user_id' => $existing->user_id, 'comment' => 'Mensaje previo conservado.']);
        Storage::disk('local')->put('tickets/legacy/conservar.txt', 'Archivo anterior intacto.');
        $attachment = TicketAttachment::create([
            'ticket_id' => $existing->id, 'comment_id' => $comment->id,
            'file_path' => 'tickets/legacy/conservar.txt', 'file_name' => 'conservar.txt',
            'file_type' => 'text/plain', 'file_size' => 25, 'uploaded_by' => $existing->user_id,
        ]);
        $beforeTicket = $existing->fresh()->getAttributes();
        $beforeAttachment = $attachment->fresh()->getAttributes();
        $beforeComment = $comment->fresh()->getAttributes();
        if ($guest) Auth::logout(); else $this->actingAs($existing->user);
        $url = $reply
            ? ($guest ? route('tickets.guest.comment', $existing->guest_token) : route('tickets.addComment', $existing))
            : route($guest ? 'tickets.guest.store' : 'tickets.store');
        $payload = $reply ? ['comment' => 'Esta respuesta solo debe guardarse con todos sus archivos.'] : [
            'submission_token' => (string) Str::uuid(), 'title' => 'Nuevo caso con archivos',
            'guest_name' => 'Invitado', 'guest_email' => $existing->guest_email ?? 'new-files@example.test',
        ];
        $this->post($url, $payload + ['attachments' => [
            UploadedFile::fake()->createWithContent('valido.txt', 'Archivo válido que tampoco debe guardarse todavía.'),
            UploadedFile::fake()->create($filename, 1, $mime),
        ]])->assertRedirect()->assertSessionHasErrors('attachments.1');
        $this->assertStringContainsString('El formato del archivo no está permitido.', session('errors')->first('attachments.1'));
        $this->assertSame($beforeTicket, $existing->fresh()->getAttributes());
        $this->assertSame($beforeAttachment, $attachment->fresh()->getAttributes());
        $this->assertSame($beforeComment, $comment->fresh()->getAttributes());
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('comentarios_ticket', 1);
        $this->assertDatabaseCount('adjuntos_ticket', 1);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
        $this->assertSame(['tickets/legacy/conservar.txt'], Storage::disk('local')->allFiles());
        $this->assertSame('Archivo anterior intacto.', Storage::disk('local')->get($attachment->file_path));
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();

        // Mismo formulario/token después de corregir el archivo: no existe
        // comprobante de un alta que falló su validación ni un cambio de turno.
        $this->flushSession();
        $this->post($url, $payload + ['attachments' => [UploadedFile::fake()->createWithContent('detalle.txt', 'Contenido del envío corregido.')]])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseCount('tickets', $reply ? 1 : 2);
        $this->assertDatabaseCount('comentarios_ticket', $reply ? 2 : 1);
        $this->assertDatabaseCount('adjuntos_ticket', 2);
        $this->assertSame($beforeAttachment, $attachment->fresh()->getAttributes());
        $this->assertSame($beforeComment, $comment->fresh()->getAttributes());
        $this->assertSame('Archivo anterior intacto.', Storage::disk('local')->get($attachment->file_path));
        if ($reply) {
            $this->assertSame(Ticket::STATUS_IN_PROGRESS, $existing->fresh()->status);
            $this->assertNull($existing->fresh()->response_deadline_at);
        } else {
            $this->assertSame($beforeTicket, $existing->fresh()->getAttributes());
        }
    }

    public function test_la_respuesta_del_agente_con_archivo_invalido_no_entrega_el_turno_ni_envia_avisos(): void
    {
        $ticket = $this->ticket(false);
        $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS, 'last_response_request_at' => null, 'response_deadline_at' => null]);
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs($ticket->assignedTo)->post(route('tickets.addComment', $ticket), [
            'comment' => 'El solicitante todavía no debe recibir este mensaje.', 'request_info' => 1,
            'attachments' => [UploadedFile::fake()->create('captura.heic', 1, 'image/heic')],
        ])->assertSessionHasErrors('attachments.0');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }
}
