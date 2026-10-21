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
        $cases = [
            'alta registrada: extensión inválida' => [false, false, 'captura.heic', 'image/heic'],
            'alta invitado: extensión inválida' => [true, false, 'captura.heic', 'image/heic'],
            'respuesta registrada: extensión inválida' => [false, true, 'captura.heic', 'image/heic'],
            'respuesta invitado: extensión inválida' => [true, true, 'captura.heic', 'image/heic'],
            'alta registrada: MIME inválido' => [false, false, 'captura.jpg', 'application/x-php'],
            'alta invitado: MIME inválido' => [true, false, 'captura.jpg', 'application/x-php'],
            'respuesta registrada: MIME inválido' => [false, true, 'captura.jpg', 'application/x-php'],
            'respuesta invitado: MIME inválido' => [true, true, 'captura.jpg', 'application/x-php'],
        ];
        foreach ([false, true] as $guest) {
            foreach ([false, true] as $reply) {
                foreach (['image/jpg', 'image/pjpeg'] as $mime) {
                    $cases[($guest ? 'invitado' : 'registrado').($reply ? ' respuesta ' : ' alta ').$mime.' falso'] = [$guest, $reply, 'falso.jpg', $mime];
                }
            }
        }
        return $cases;
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

    public static function jpegAliases(): array
    {
        $cases = [];
        foreach ([false, true] as $guest) {
            foreach ([false, true] as $reply) {
                foreach (['image/jpg', 'image/pjpeg'] as $mime) {
                    $cases[($guest ? 'invitado' : 'registrado').($reply ? ' respuesta ' : ' alta ').$mime] = [$guest, $reply, $mime];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('jpegAliases')]
    public function test_alias_jpeg_real_se_admite_y_guarda_mime_canonico_sin_cambiar_archivos_anteriores(bool $guest, bool $reply, string $mime): void
    {
        $existing = $this->ticket($guest);
        Storage::disk('local')->put('tickets/legacy/anterior.txt', 'Adjunto anterior intacto.');
        $previous = TicketAttachment::create([
            'ticket_id' => $existing->id, 'file_path' => 'tickets/legacy/anterior.txt',
            'file_name' => 'anterior.txt', 'file_type' => 'text/plain', 'file_size' => 24,
            'uploaded_by' => $existing->user_id,
        ]);
        $before = $previous->fresh()->getAttributes();
        $beforeTicket = $existing->getAttributes();
        // El fake cambia solo la detección del MIME en el servidor; su contenido
        // es un JPEG real generado por GD, no un archivo vacío con nombre .jpg.
        $file = UploadedFile::fake()->image('captura.JPEG', 24, 16)->mimeType($mime);
        $bytes = file_get_contents($file->getPathname());
        if ($guest) Auth::logout(); else $this->actingAs($existing->user);
        $url = $reply
            ? ($guest ? route('tickets.guest.comment', $existing->guest_token) : route('tickets.addComment', $existing))
            : route($guest ? 'tickets.guest.store' : 'tickets.store');
        $payload = $reply ? ['comment' => 'Respuesta con una imagen JPEG válida.'] : [
            'submission_token' => (string) Str::uuid(), 'title' => 'Nuevo caso con imagen JPEG',
            'guest_name' => 'Invitado', 'guest_email' => 'jpeg-compatibility@example.test',
        ];
        $this->post($url, $payload + ['attachments' => [$file]])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $saved = TicketAttachment::where('id', '!=', $previous->id)->sole();
        $this->assertSame('image/jpeg', $saved->file_type);
        $this->assertSame('captura.JPEG', $saved->file_name);
        $this->assertStringEndsWith('.jpeg', $saved->file_path);
        $this->assertSame($bytes, Storage::disk('local')->get($saved->file_path));
        $this->assertSame($before, $previous->fresh()->getAttributes());
        $this->assertSame('Adjunto anterior intacto.', Storage::disk('local')->get($previous->file_path));
        $this->assertDatabaseCount('tickets', $reply ? 1 : 2);
        $this->assertDatabaseCount('comentarios_ticket', $reply ? 1 : 0);
        $this->assertDatabaseCount('adjuntos_ticket', 2);
        if ($reply) {
            $this->assertSame(Ticket::STATUS_IN_PROGRESS, $existing->fresh()->status);
            $this->assertNull($existing->fresh()->response_deadline_at);
            $this->assertSame($existing->id, $saved->ticket_id);
            $this->assertNotNull($saved->comment_id);
        } else {
            $this->assertSame($beforeTicket, $existing->fresh()->getAttributes());
            $this->assertNotSame($existing->id, $saved->ticket_id);
            $this->assertNull($saved->comment_id);
        }
    }

    public function test_los_alias_jpeg_no_admiten_contenido_falso_otros_formatos_o_tipos_genericos(): void
    {
        $controller = new \App\Http\Controllers\TicketController();
        $validate = new \ReflectionMethod($controller, 'isValidFile');
        foreach (['image/jpg', 'image/pjpeg'] as $alias) {
            foreach (['texto que no es una imagen', '<?php echo "No ejecutar";', "\xFF\xD8\xFF\xE0JPEG incompleto"] as $content) {
                $file = UploadedFile::fake()->createWithContent('falso.jpg', $content)->mimeType($alias);
                $this->assertFalse($validate->invoke($controller, $file));
            }
            $png = UploadedFile::fake()->image('real.png', 24, 16);
            $renamed = UploadedFile::fake()->createWithContent('renombrado.jpg', file_get_contents($png->getPathname()))->mimeType($alias);
            $this->assertFalse($validate->invoke($controller, $renamed));
            $badExtension = UploadedFile::fake()->image('ejecutable.php', 24, 16)->mimeType($alias);
            $this->assertFalse($validate->invoke($controller, $badExtension));
        }
        foreach (['application/octet-stream', 'application/x-php', 'text/html', 'image/webp'] as $mime) {
            $file = UploadedFile::fake()->image('captura.jpg', 24, 16)->mimeType($mime);
            $this->assertFalse($validate->invoke($controller, $file));
        }
    }

    public function test_imagenes_estandar_y_otros_formatos_permitidos_conservan_su_validacion(): void
    {
        $controller = new \App\Http\Controllers\TicketController();
        $validate = new \ReflectionMethod($controller, 'isValidFile');
        foreach (['captura.jpg', 'captura.jpeg', 'captura.JPG', 'captura.JPEG', 'captura.png', 'captura.gif'] as $name) {
            $this->assertTrue($validate->invoke($controller, UploadedFile::fake()->image($name, 24, 16)), $name);
        }
        foreach ([['documento.pdf', 'application/pdf'], ['documento.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['archivo.zip', 'application/zip'], ['detalle.txt', 'text/plain'], ['video.mp4', 'video/mp4']] as [$name, $mime]) {
            $this->assertTrue($validate->invoke($controller, UploadedFile::fake()->create($name, 1, $mime)), $name);
        }
    }

    public function test_jpeg_progresivo_admite_alias_sin_convertir_ni_reescribir_la_imagen(): void
    {
        $image = imagecreatetruecolor(24, 16);
        imageinterlace($image, true);
        ob_start();
        imagejpeg($image);
        $bytes = ob_get_clean();
        $this->assertStringContainsString("\xFF\xC2", $bytes);
        $controller = new \App\Http\Controllers\TicketController();
        $validate = new \ReflectionMethod($controller, 'isValidFile');
        foreach (['image/jpg', 'image/pjpeg'] as $alias) {
            $file = UploadedFile::fake()->createWithContent('progresivo.jpg', $bytes)->mimeType($alias);
            $this->assertTrue($validate->invoke($controller, $file));
            $this->assertSame($bytes, file_get_contents($file->getPathname()));
        }
    }
}
