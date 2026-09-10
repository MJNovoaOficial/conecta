<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * El recorrido completo de un invitado: alguien sin cuenta que necesita ayuda.
 *
 * Es el camino más frágil de la plataforma, porque nadie de la empresa lo usa
 * a diario y por lo tanto nadie nota cuando se rompe. Ya pasó una vez: una
 * plantilla esperaba una variable con otro nombre y durante semanas TODOS los
 * tickets de invitado devolvieron error 500 al abrirlos. El invitado recibía
 * su enlace por correo, hacía clic, y veía una pantalla de error.
 *
 * Estas pruebas existen para que eso no vuelva a pasar en silencio.
 */
class TicketInvitadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El formulario limita a 5 tickets por hora y por IP. Entre pruebas el
        // contador se arrastra y haría fallar a las últimas por una razón que
        // no tiene que ver con lo que se está probando.
        RateLimiter::clear('guest_ticket:127.0.0.1');

        Notification::fake();
    }

    private function datosDeTicket(array $extra = []): array
    {
        return array_merge([
            'guest_name'  => 'Ana Perez',
            'guest_email' => 'ana.perez@ejemplo.cl',
            'title'       => 'No puedo entrar al sistema',
            'description' => 'Escribo mi clave y me dice que es incorrecta.',
        ], $extra);
    }

    // ── Crear ─────────────────────────────────────────────────────────

    public function test_un_invitado_puede_crear_un_ticket_sin_cuenta(): void
    {
        $respuesta = $this->post(route('tickets.guest.store'), $this->datosDeTicket());

        $ticket = Ticket::first();

        $this->assertNotNull($ticket, 'el ticket debería haberse creado');
        $this->assertNull($ticket->user_id, 'un invitado no tiene cuenta asociada');
        $this->assertSame('ana.perez@ejemplo.cl', $ticket->guest_email);
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertNotEmpty($ticket->guest_token, 'sin token el invitado no puede volver a su ticket');

        $respuesta->assertRedirect(route('tickets.guest.show', ['token' => $ticket->guest_token]));
    }

    public function test_el_ticket_de_invitado_recibe_plazos_de_sla(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());

        $ticket = Ticket::first();

        $this->assertNotNull($ticket->sla_response_deadline_at);
        $this->assertNotNull($ticket->sla_resolution_deadline_at);
        $this->assertTrue(
            $ticket->sla_resolution_deadline_at->gt($ticket->sla_response_deadline_at),
            'el plazo de resolución no puede vencer antes que el de respuesta'
        );
    }

    public function test_el_correo_es_obligatorio_porque_es_la_unica_forma_de_contactarlo(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket(['guest_email' => '']))
            ->assertSessionHasErrors('guest_email');

        $this->assertSame(0, Ticket::count());
    }

    // ── Abrir el ticket con el token ──────────────────────────────────

    public function test_el_invitado_puede_abrir_su_ticket_con_el_enlace(): void
    {
        // Esta es la prueba que faltaba cuando se rompió. Basta con que la
        // página cargue: el error era de plantilla y devolvía 500 siempre.
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());
        $ticket = Ticket::first();

        $this->get(route('tickets.guest.show', ['token' => $ticket->guest_token]))
            ->assertOk()
            ->assertSee($ticket->ticket_number);
    }

    public function test_un_token_inventado_no_abre_ningun_ticket(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());

        $this->get(route('tickets.guest.show', ['token' => 'token-que-no-existe']))
            ->assertNotFound();
    }

    public function test_el_token_de_un_invitado_no_abre_el_ticket_de_otro(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());
        RateLimiter::clear('guest_ticket:127.0.0.1');
        $this->post(route('tickets.guest.store'), $this->datosDeTicket([
            'guest_email' => 'otro@ejemplo.cl',
            'title'       => 'La impresora no responde',
        ]));

        [$primero, $segundo] = Ticket::orderBy('id')->get()->all();

        // Crear un ticket deja en sesión el aviso "Ticket creado: TK-...", que
        // se pinta en la página siguiente. Sin limpiarlo, la prueba encuentra
        // el número del segundo ticket en el aviso y no en los datos, que es
        // lo que realmente interesa comprobar.
        $this->flushSession();

        $this->get(route('tickets.guest.show', ['token' => $primero->guest_token]))
            ->assertOk()
            ->assertSee($primero->ticket_number)
            ->assertDontSee($segundo->ticket_number)
            ->assertDontSee('otro@ejemplo.cl');
    }

    public function test_soporte_puede_abrir_un_ticket_de_invitado(): void
    {
        // Esta es la prueba del error que estuvo semanas dando 500. La página
        // del invitado cargaba bien; la que reventaba era esta, porque el
        // enlace de seguimiento solo se pinta para quien atiende. Es decir:
        // fallaba justo para el lado que nadie estaba mirando.
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());
        $ticket = Ticket::first();

        $this->flushSession();

        $this->actingAs(User::factory()->soporte()->create())
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee($ticket->guest_token, escape: false);
    }

    // ── Responder ─────────────────────────────────────────────────────

    public function test_el_invitado_puede_responder_su_propio_ticket(): void
    {
        // Sin esto, pedirle información a un invitado era un callejón sin
        // salida: el ticket quedaba esperando una respuesta que no tenía cómo
        // enviar, y el cierre automático lo cerraba por falta de respuesta.
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());
        $ticket = Ticket::first();

        $this->post(route('tickets.guest.comment', ['token' => $ticket->guest_token]), [
            'comment' => 'Probe reiniciando el equipo y sigue igual.',
        ])->assertRedirect();

        $this->assertSame(1, $ticket->comments()->count());
        $this->assertStringContainsString('reiniciando', $ticket->comments()->first()->comment);
    }

    public function test_no_se_puede_comentar_con_un_token_ajeno(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());
        $ticket = Ticket::first();

        $this->post(route('tickets.guest.comment', ['token' => 'token-falso']), [
            'comment' => 'Deberia poder escribir aqui.',
        ])->assertNotFound();

        $this->assertSame(0, $ticket->comments()->count());
    }

    // ── Reabrir ───────────────────────────────────────────────────────

    public function test_el_invitado_puede_reabrir_su_ticket_si_la_solucion_no_sirvio(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());

        $ticket = Ticket::first();
        $ticket->update(['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now()]);

        $this->post(route('tickets.guest.reopen', ['token' => $ticket->guest_token]), [
            'motivo' => 'El problema volvio a aparecer al dia siguiente.',
        ])->assertRedirect();

        $ticket->refresh();

        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);
        $this->assertSame(1, (int) $ticket->reopened_count);
        $this->assertNotNull($ticket->reopened_at);
        $this->assertNull($ticket->resolved_at, 'al reabrir deja de estar resuelto');
        $this->assertSame(
            1,
            $ticket->comments()->count(),
            'el motivo queda como comentario para que soporte sepa qué retomar'
        );
    }

    public function test_solo_se_reabre_un_ticket_resuelto(): void
    {
        // Un ticket cerrado no se reabre desde el enlace: si el cierre fue
        // automático por falta de respuesta, el camino es abrir uno nuevo.
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());

        $ticket = Ticket::first();
        $ticket->update(['status' => Ticket::STATUS_CLOSED, 'closed_at' => now()]);

        $this->post(route('tickets.guest.reopen', ['token' => $ticket->guest_token]), [
            'motivo' => 'Quiero que revisen esto otra vez, por favor.',
        ]);

        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertSame(0, (int) $ticket->fresh()->reopened_count);
    }

    public function test_reabrir_exige_explicar_que_sigue_fallando(): void
    {
        $this->post(route('tickets.guest.store'), $this->datosDeTicket());

        $ticket = Ticket::first();
        $ticket->update(['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now()]);

        $this->post(route('tickets.guest.reopen', ['token' => $ticket->guest_token]), ['motivo' => 'no'])
            ->assertSessionHasErrors('motivo');

        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
    }
}
