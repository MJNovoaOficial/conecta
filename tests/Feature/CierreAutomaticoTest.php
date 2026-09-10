<?php

namespace Tests\Feature;

use App\Jobs\AutoCloseTicketJob;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Cierre automático de tickets que quedaron esperando al solicitante.
 *
 * Este trabajo cierra tickets sin que nadie lo pida, así que lo que importa
 * no es tanto que cierre bien como que NO cierre lo que no corresponde. Un
 * cierre indebido hace desaparecer el problema de la bandeja de soporte y
 * deja a la persona sin respuesta, sin que nadie se entere.
 *
 * Existió una versión que cerraba también los tickets cuyo plazo de SLA había
 * vencido, como si vencer el plazo significara estar resuelto. Se eliminó. La
 * mitad de estas pruebas está para que no vuelva.
 */
class CierreAutomaticoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Mail::fake();
    }

    private function ticket(array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number'        => 'TK-' . fake()->unique()->numerify('##########'),
            'title'                => 'Necesito ayuda con el correo',
            'description'          => 'Descripcion de prueba.',
            'status'               => Ticket::STATUS_PENDING_USER,
            'priority'             => 'medium',
            'response_deadline_at' => now()->subHour(),   // ya vencido
            'user_responded_at'    => null,
        ], $extra));
    }

    // ── Lo que sí debe cerrar ─────────────────────────────────────────

    public function test_cierra_un_ticket_que_nadie_respondio_dentro_del_plazo(): void
    {
        $ticket = $this->ticket();

        (new AutoCloseTicketJob)->handle();

        $ticket->refresh();

        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->status);
        $this->assertNotNull($ticket->closed_at, 'sin closed_at el tiempo de atención sigue corriendo');
    }

    public function test_el_cierre_queda_registrado_en_el_historial_y_la_auditoria(): void
    {
        // Nadie firma este cierre, así que tiene que quedar rastro de que lo
        // hizo el sistema y por qué. Si alguien reclama, es lo único que hay.
        $ticket = $this->ticket();

        (new AutoCloseTicketJob)->handle();

        $historial = TicketHistory::where('ticket_id', $ticket->id)->where('action', 'auto_closed')->first();

        $this->assertNotNull($historial);
        $this->assertNull($historial->user_id, 'lo cerró el sistema, no una persona');
        $this->assertSame(Ticket::STATUS_PENDING_USER, $historial->old_value);
        $this->assertSame(Ticket::STATUS_CLOSED, $historial->new_value);

        $this->assertSame(
            1,
            AuditLog::where('action', 'ticket.auto_closed_no_response')->count()
        );
    }

    // ── Lo que NO debe cerrar ─────────────────────────────────────────

    public function test_no_cierra_el_ticket_de_quien_si_respondio(): void
    {
        // Le pedimos información, la entregó, y le cerramos el ticket igual.
        // Es el peor caso posible: castiga justo a quien colaboró.
        $ticket = $this->ticket(['user_responded_at' => now()->subMinutes(30)]);

        (new AutoCloseTicketJob)->handle();

        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
    }

    public function test_no_cierra_antes_de_que_venza_el_plazo(): void
    {
        $ticket = $this->ticket(['response_deadline_at' => now()->addHours(3)]);

        (new AutoCloseTicketJob)->handle();

        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
    }

    public function test_no_cierra_un_ticket_sin_plazo_de_respuesta(): void
    {
        $ticket = $this->ticket(['response_deadline_at' => null]);

        (new AutoCloseTicketJob)->handle();

        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
    }

    public function test_no_cierra_un_ticket_que_soporte_no_alcanzo_a_resolver(): void
    {
        // ESTA es la prueba importante. Hubo una versión que cerraba los
        // tickets con el plazo de resolución vencido, como si incumplir el
        // SLA significara que el problema está resuelto. No lo está: el
        // ticket desaparecía de la bandeja y la persona quedaba sin respuesta.
        $abierto = $this->ticket([
            'status'                     => Ticket::STATUS_OPEN,
            'response_deadline_at'       => null,
            'sla_resolution_deadline_at' => now()->subDays(3),
        ]);

        $enCurso = $this->ticket([
            'status'                     => Ticket::STATUS_IN_PROGRESS,
            'response_deadline_at'       => null,
            'sla_resolution_deadline_at' => now()->subDays(3),
        ]);

        (new AutoCloseTicketJob)->handle();

        $this->assertSame(Ticket::STATUS_OPEN, $abierto->fresh()->status);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $enCurso->fresh()->status);
        $this->assertSame(0, AuditLog::where('action', 'ticket.auto_closed_no_response')->count());
    }

    public function test_no_toca_tickets_en_otros_estados(): void
    {
        $estados = [
            Ticket::STATUS_OPEN,
            Ticket::STATUS_IN_PROGRESS,
            Ticket::STATUS_FORWARDED,
            Ticket::STATUS_RESOLVED,
            Ticket::STATUS_CLOSED,
        ];

        $tickets = [];
        foreach ($estados as $estado) {
            $tickets[$estado] = $this->ticket(['status' => $estado]);
        }

        (new AutoCloseTicketJob)->handle();

        foreach ($tickets as $estado => $ticket) {
            $this->assertSame(
                $estado,
                $ticket->fresh()->status,
                "un ticket en estado {$estado} no debería cerrarse por falta de respuesta"
            );
        }
    }

    public function test_no_vuelve_a_cerrar_ni_a_auditar_un_ticket_ya_cerrado(): void
    {
        $this->ticket();

        (new AutoCloseTicketJob)->handle();
        (new AutoCloseTicketJob)->handle();
        (new AutoCloseTicketJob)->handle();

        $this->assertSame(
            1,
            AuditLog::where('action', 'ticket.auto_closed_no_response')->count(),
            'tres pasadas no pueden dejar tres registros de auditoría del mismo cierre'
        );
    }

    // ── A quién avisa ─────────────────────────────────────────────────

    public function test_avisa_al_usuario_registrado_que_su_ticket_se_cerro(): void
    {
        $usuario = User::factory()->create();
        $ticket  = $this->ticket(['user_id' => $usuario->id]);

        (new AutoCloseTicketJob)->handle();

        $this->assertSame(
            1,
            \App\Models\Notificacion::where('user_id', $usuario->id)->where('type', 'closed')->count()
        );
    }

    public function test_avisa_por_correo_al_invitado_que_no_tiene_cuenta(): void
    {
        $this->ticket([
            'user_id'     => null,
            'guest_email' => 'invitado@ejemplo.cl',
            'guest_name'  => 'Persona Invitada',
            'guest_token' => str_repeat('a', 40),
        ]);

        (new AutoCloseTicketJob)->handle();

        // assertQueued y no assertSent: el correo implementa ShouldQueue, o sea
        // que no se envía en el momento sino que se deja en la cola.
        //
        // Vale la pena entender lo que eso significa fuera de las pruebas: sin
        // un proceso queue:work corriendo en el servidor, este correo se guarda
        // en la tabla de trabajos y NO SALE NUNCA. Sin error, sin aviso. Es
        // exactamente lo que hoy pasa en producción con todos los correos.
        Mail::assertQueued(\App\Mail\GuestTicketAutoClosedMail::class);
    }

    public function test_un_fallo_de_correo_no_deja_el_cierre_a_medias(): void
    {
        // El ticket ya se cerró y la auditoría ya se escribió. Si el correo
        // falla, el trabajo no puede reventar y dejar todo inconsistente.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('servidor de correo caido'));

        $ticket = $this->ticket([
            'user_id'     => null,
            'guest_email' => 'invitado@ejemplo.cl',
            'guest_token' => str_repeat('b', 40),
        ]);

        (new AutoCloseTicketJob)->handle();

        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertSame(1, AuditLog::where('action', 'ticket.auto_closed_no_response')->count());
    }
}
