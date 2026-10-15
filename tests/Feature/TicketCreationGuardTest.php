<?php

namespace Tests\Feature;

use App\Mail\GuestTicketCreatedMail;
use App\Models\AuditLog;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Support\TicketCreationGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketCreationGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Mail::fake();
        Storage::fake('local');
        RateLimiter::clear('guest_ticket:127.0.0.1');
    }

    private function payload(array $extra = []): array
    {
        return array_merge(['submission_token' => (string) Str::uuid(), 'title' => 'No tengo red',
            'description' => 'La conexión dejó de funcionar.'], $extra);
    }

    public function test_repetir_el_mismo_envio_crea_un_solo_ticket_y_un_solo_aviso(): void
    {
        $user = User::factory()->create();
        User::factory()->soporte()->create();
        $this->actingAs($user);
        $data = $this->payload();
        for ($i = 0; $i < 12; $i++) {
            $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors()
                ->assertRedirect(route('tickets.show', Ticket::sole()));
        }
        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, AuditLog::where('action', 'ticket.created')->count());
        $this->assertSame(2, Notificacion::where('type', 'new_ticket')->count());
        Notification::assertSentToTimes($user, TicketCreatedNotification::class, 1);
        $this->assertSame(1, RateLimiter::attempts('create_ticket:'.$user->id));
    }

    public function test_el_invitado_reintenta_y_recibe_el_mismo_enlace_sin_otro_correo(): void
    {
        $data = $this->payload(['guest_name' => 'Invitado', 'guest_email' => 'guest@example.test']);
        $this->post(route('tickets.guest.store'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $ticket = Ticket::sole();
        $this->post(route('tickets.guest.store'), $data)->assertSessionHasNoErrors()
            ->assertRedirect(route('tickets.guest.show', $ticket->guest_token));
        $this->assertSame(1, Ticket::count());
        Mail::assertQueued(GuestTicketCreatedMail::class, 1);
        $this->assertSame(1, RateLimiter::attempts('guest_ticket:127.0.0.1'));
    }

    public function test_dos_solicitudes_intencionales_con_igual_texto_siguen_siendo_distintas(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('tickets.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('tickets.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame(2, Ticket::count());
    }

    public function test_usuarios_distintos_en_la_misma_ip_no_comparten_el_bloqueo(): void
    {
        $data = $this->payload();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($first)->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($second)->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::where('user_id', $first->id)->count());
        $this->assertSame(1, Ticket::where('user_id', $second->id)->count());
    }

    public function test_invitados_distintos_no_comparten_el_comprobante(): void
    {
        $data = $this->payload(['guest_name' => 'Invitado', 'guest_email' => 'primero@example.test']);
        $this->post(route('tickets.guest.store'), $data)->assertSessionHasNoErrors();
        $data['guest_email'] = 'segundo@example.test';
        $this->post(route('tickets.guest.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(2, Ticket::count());
        $this->assertSame(1, Ticket::where('guest_email', 'primero@example.test')->count());
        $this->assertSame(1, Ticket::where('guest_email', 'segundo@example.test')->count());
    }

    public function test_una_peticion_en_curso_impide_que_otra_inserte_y_luego_se_puede_reintentar(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $data = $this->payload();
        $key = 'ticket-create:'.hash('sha256', 'user:'.$user->id.'|'.$data['submission_token']);
        $lock = Cache::lock($key.':lock', 120);
        $this->assertTrue($lock->get());
        try {
            $this->post(route('tickets.store'), $data)->assertSessionHasErrors('general');
            $this->assertSame(0, Ticket::count());
        } finally {
            $lock->release();
        }
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
    }

    public function test_validacion_fallida_no_consume_el_identificador_ni_deja_bloqueado_el_formulario(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->payload(['title' => '']);
        $this->post(route('tickets.store'), $data)->assertSessionHasErrors('title');
        $this->assertSame(0, Ticket::count());
        $data['title'] = 'Solicitud corregida';
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
    }

    public function test_token_malformado_no_crea_un_ticket(): void
    {
        $this->actingAs(User::factory()->create())->post(route('tickets.store'),
            $this->payload(['submission_token' => ['invalido']]))->assertSessionHasErrors('submission_token');
        $this->assertSame(0, Ticket::count());
        $this->get(route('tickets.create'))->assertOk()->assertSee('name="submission_token"', false);
    }

    public function test_si_falla_el_comprobante_se_revierte_la_creacion_del_ticket(): void
    {
        $request = Request::create('/tickets', 'POST');
        $request->attributes->set('ticket_creation_key', 'rollback-'.Str::uuid());
        Cache::shouldReceive('put')->once()->andReturn(false);
        try {
            TicketCreationGuard::create($request, ['ticket_number' => 'ROLLBACK-'.Str::uuid(),
                'title' => 'No debe persistir', 'priority' => 'medium', 'status' => 'open']);
            $this->fail('Debe revertirse la creación si no se registra el comprobante.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('comprobante', $e->getMessage());
        }
        $this->assertSame(0, Ticket::count());
    }

    public function test_reintento_no_duplica_adjuntos_ni_vuelve_a_escribir_el_archivo(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->payload(['attachments' => [UploadedFile::fake()->createWithContent('detalle.txt', 'Contenido original')]]);
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $attachment = TicketAttachment::sole();
        $data['attachments'] = [UploadedFile::fake()->createWithContent('detalle.txt', 'Contenido del reintento')];
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
        $this->assertSame(1, TicketAttachment::count());
        $this->assertSame('Contenido original', Storage::disk('local')->get($attachment->file_path));
    }

    public function test_archivos_con_el_mismo_nombre_conservan_sus_contenidos_separados(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->payload(['attachments' => [
            UploadedFile::fake()->createWithContent('captura.txt', 'Primer archivo'),
            UploadedFile::fake()->createWithContent('captura.txt', 'Segundo archivo'),
        ]]);
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $attachments = TicketAttachment::orderBy('id')->get();
        $this->assertCount(2, $attachments);
        $this->assertSame('Primer archivo', Storage::disk('local')->get($attachments[0]->file_path));
        $this->assertSame('Segundo archivo', Storage::disk('local')->get($attachments[1]->file_path));
        $this->assertNotSame($attachments[0]->file_path, $attachments[1]->file_path);
        $this->assertSame('captura.txt', $attachments[0]->file_name);
        $this->assertSame('captura.txt', $attachments[1]->file_name);
    }

    public function test_funciona_con_cache_database_como_en_el_servidor(): void
    {
        config(['cache.default' => 'database']);
        $this->actingAs(User::factory()->create());
        $data = $this->payload();
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('tickets.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, Ticket::count());
    }

    public function test_adjuntos_de_respuestas_distintas_no_se_sobrescriben_por_nombre(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('tickets.store'), $this->payload())->assertSessionHasNoErrors();
        $ticket = Ticket::sole();
        $ticket->update(['assigned_to' => User::factory()->soporte()->create()->id, 'status' => 'in_progress']);
        foreach (['Primera respuesta', 'Segunda respuesta'] as $content) {
            $this->post(route('tickets.addComment', $ticket), [
                'comment' => $content,
                'attachments' => [UploadedFile::fake()->createWithContent('captura.txt', $content)],
            ])->assertSessionHasNoErrors();
        }
        $attachments = TicketAttachment::orderBy('id')->get();
        $this->assertCount(2, $attachments);
        $this->assertSame('Primera respuesta', Storage::disk('local')->get($attachments[0]->file_path));
        $this->assertSame('Segunda respuesta', Storage::disk('local')->get($attachments[1]->file_path));
        $this->assertNotSame($attachments[0]->file_path, $attachments[1]->file_path);
    }

    public function test_fallo_despues_de_guardar_el_ticket_no_crea_otro_al_reintentar(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/tickets', 'POST', $this->payload());
        $request->setUserResolver(fn () => $user);
        $attributes = ['ticket_number' => 'GUARD-'.Str::uuid(), 'user_id' => $user->id,
            'title' => 'Ticket persistido', 'priority' => 'medium', 'status' => 'open'];
        try {
            TicketCreationGuard::run($request, function () use ($request, $attributes) {
                TicketCreationGuard::create($request, $attributes);
                throw new \RuntimeException('Fallo posterior simulado');
            });
            $this->fail('Debió propagarse el fallo simulado.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fallo posterior simulado', $e->getMessage());
        }
        $response = TicketCreationGuard::run($request, function () {
            $this->fail('No debe volver a ejecutar la creación.');
        });
        $this->assertSame(route('tickets.show', Ticket::sole()), $response->getTargetUrl());
        $this->assertSame(1, Ticket::count());
    }

    public function test_formularios_incluyen_token_y_bloqueo_sin_exigir_login_al_invitado(): void
    {
        $this->get(route('tickets.guest.create'))->assertOk()
            ->assertSee('data-ticket-create', false)->assertSee('name="submission_token"', false)
            ->assertSee('conectaTicketCreateGuard', false);
        $this->actingAs(User::factory()->create())->get(route('tickets.create'))->assertOk()
            ->assertSee('type="submit" form="modalTicketForm"', false)
            ->assertSee('name="submission_token"', false)->assertSee('form.requestSubmit()', false)
            ->assertDontSee('form.submit()', false);
    }
}
