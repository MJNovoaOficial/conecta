<?php

namespace Tests\Feature;

use App\Models\EncuestaSatisfaccion;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * La encuesta de satisfacción.
 *
 * Lo que más importa no es que se guarde, sino que la nota sea confiable: que
 * la ponga quien recibió la ayuda y nadie más, una sola vez, y que quede con el
 * agente que atendía. Un promedio de satisfacción que se puede inflar o que
 * castiga al agente equivocado es peor que no tener ninguno.
 */
class EncuestaSatisfaccionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function ticketCerrado(?User $dueno, ?User $agente = null, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'TK-' . fake()->unique()->numerify('##########'),
            'user_id'       => $dueno?->id,
            'title'         => 'La impresora no imprime',
            'description'   => 'Descripcion de prueba.',
            'status'        => Ticket::STATUS_CLOSED,
            'priority'      => 'medium',
            'assigned_to'   => $agente?->id,
            'closed_at'     => now(),
        ], $extra));
    }

    private function ticketDeInvitadoCerrado(?User $agente = null): Ticket
    {
        return $this->ticketCerrado(null, $agente, [
            'guest_name'  => 'Persona Invitada',
            'guest_email' => 'invitado@ejemplo.cl',
            'guest_token' => str_repeat('k', 40),
        ]);
    }

    // ── Responder ─────────────────────────────────────────────────────

    public function test_el_solicitante_califica_la_atencion_de_su_ticket_cerrado(): void
    {
        $dueno  = User::factory()->create();
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticketCerrado($dueno, $agente);

        $this->actingAs($dueno)
            ->post(route('encuesta.responder', $ticket), [
                'calificacion' => 4,
                'comentario'   => 'Me ayudaron muy rápido.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('encuestas_satisfaccion', [
            'ticket_id'    => $ticket->id,
            'agente_id'    => $agente->id,
            'usuario_id'   => $dueno->id,
            'calificacion' => 4,
            'comentario'   => 'Me ayudaron muy rápido.',
        ]);
    }

    public function test_la_calificacion_queda_con_el_agente_que_atendia_aunque_se_reasigne(): void
    {
        // Si se contara por el agente asignado hoy, reasignar un ticket viejo
        // le trasladaría la nota a alguien que nunca lo atendió.
        $dueno    = User::factory()->create();
        $atendio  = User::factory()->soporte()->create();
        $despues  = User::factory()->soporte()->create();
        $ticket   = $this->ticketCerrado($dueno, $atendio);

        $this->actingAs($dueno)->post(route('encuesta.responder', $ticket), ['calificacion' => 5]);

        $ticket->update(['assigned_to' => $despues->id]);

        $this->assertSame($atendio->id, $ticket->encuesta()->first()->agente_id);
    }

    public function test_un_invitado_califica_con_el_token_de_su_enlace(): void
    {
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticketDeInvitadoCerrado($agente);

        $this->post(route('encuesta.invitado', $ticket->guest_token), ['calificacion' => 3])
            ->assertRedirect();

        $encuesta = $ticket->encuesta()->first();

        $this->assertNotNull($encuesta);
        $this->assertNull($encuesta->usuario_id, 'un invitado no tiene cuenta');
        $this->assertSame($agente->id, $encuesta->agente_id);
    }

    // ── Que la nota sea confiable ─────────────────────────────────────

    public function test_se_califica_una_sola_vez(): void
    {
        // Si se pudiera volver a responder, una persona molesta podría bajarle
        // el promedio a un agente mandando la encuesta muchas veces.
        $dueno  = User::factory()->create();
        $ticket = $this->ticketCerrado($dueno, User::factory()->soporte()->create());

        $this->actingAs($dueno)->post(route('encuesta.responder', $ticket), ['calificacion' => 4]);
        $this->actingAs($dueno)->post(route('encuesta.responder', $ticket), ['calificacion' => 1])
            ->assertSessionHas('error');

        $this->assertSame(1, EncuestaSatisfaccion::count());
        $this->assertSame(4, EncuestaSatisfaccion::first()->calificacion, 'la primera respuesta no se reemplaza');
    }

    public function test_otro_usuario_no_puede_calificar_un_ticket_ajeno(): void
    {
        $ticket = $this->ticketCerrado(User::factory()->create(), User::factory()->soporte()->create());

        $this->actingAs(User::factory()->create())
            ->post(route('encuesta.responder', $ticket), ['calificacion' => 1])
            ->assertForbidden();

        $this->assertSame(0, EncuestaSatisfaccion::count());
    }

    public function test_soporte_no_puede_calificarse_a_si_mismo(): void
    {
        // Ni el agente que atendió ni un administrador pueden responder en
        // nombre del solicitante.
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticketCerrado(User::factory()->create(), $agente);

        $this->actingAs($agente)
            ->post(route('encuesta.responder', $ticket), ['calificacion' => 5])
            ->assertForbidden();

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('encuesta.responder', $ticket), ['calificacion' => 5])
            ->assertForbidden();

        $this->assertSame(0, EncuestaSatisfaccion::count());
    }

    public function test_no_se_califica_un_ticket_que_todavia_no_esta_cerrado(): void
    {
        // Mientras está resuelto, la persona todavía tiene que decidir si la
        // solución le sirvió.
        $dueno = User::factory()->create();

        foreach ([Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_RESOLVED] as $estado) {
            $ticket = $this->ticketCerrado($dueno, null, ['status' => $estado, 'closed_at' => null]);

            $this->actingAs($dueno)
                ->post(route('encuesta.responder', $ticket), ['calificacion' => 5])
                ->assertSessionHas('error');
        }

        $this->assertSame(0, EncuestaSatisfaccion::count());
    }

    public function test_un_ticket_cerrado_por_falta_de_respuesta_no_admite_encuesta(): void
    {
        // Se cerró porque el solicitante no contestó. Que eso le baje la nota
        // al agente sería injusto.
        $dueno  = User::factory()->create();
        $ticket = $this->ticketCerrado($dueno, User::factory()->soporte()->create());

        TicketHistory::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => null,
            'action'     => 'auto_closed',
            'old_value'  => Ticket::STATUS_PENDING_USER,
            'new_value'  => Ticket::STATUS_CLOSED,
            'field_name' => 'status',
        ]);

        $this->assertFalse($ticket->admiteEncuesta());

        $this->actingAs($dueno)
            ->post(route('encuesta.responder', $ticket), ['calificacion' => 1])
            ->assertSessionHas('error');

        $this->assertSame(0, EncuestaSatisfaccion::count());
    }

    public function test_la_calificacion_tiene_que_ser_una_de_las_cinco_caritas(): void
    {
        $dueno  = User::factory()->create();
        $ticket = $this->ticketCerrado($dueno);

        foreach ([0, 6, 'muy bien', ''] as $valor) {
            $this->actingAs($dueno)
                ->post(route('encuesta.responder', $ticket), ['calificacion' => $valor])
                ->assertSessionHasErrors('calificacion');
        }

        $this->assertSame(0, EncuestaSatisfaccion::count());
    }

    public function test_un_token_inventado_no_califica_nada(): void
    {
        $this->ticketDeInvitadoCerrado();

        $this->post('/tickets/guest/' . str_repeat('z', 40) . '/encuesta', ['calificacion' => 1])
            ->assertNotFound();

        $this->assertSame(0, EncuestaSatisfaccion::count());
    }

    // ── Lo que se ve en pantalla ──────────────────────────────────────

    public function test_el_solicitante_ve_la_encuesta_y_despues_el_agradecimiento(): void
    {
        $dueno  = User::factory()->create();
        $ticket = $this->ticketCerrado($dueno, User::factory()->soporte()->create());

        $this->actingAs($dueno)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('¿Cómo te atendimos?');

        $this->actingAs($dueno)->post(route('encuesta.responder', $ticket), ['calificacion' => 5]);

        // El aviso de "gracias" queda en sesión y se pinta en la página
        // siguiente: se limpia para comprobar la tarjeta, no el aviso.
        $this->flushSession();

        $this->actingAs($dueno)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('¿Cómo te atendimos?')
            ->assertSee('Ya calificaste esta atención');
    }

    public function test_el_invitado_ve_la_encuesta_en_su_enlace(): void
    {
        $ticket = $this->ticketDeInvitadoCerrado(User::factory()->soporte()->create());

        $this->get(route('tickets.guest.show', $ticket->guest_token))
            ->assertOk()
            ->assertSee('¿Cómo te atendimos?');
    }

    public function test_soporte_ve_la_calificacion_y_el_comentario_en_el_ticket(): void
    {
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticketCerrado(User::factory()->create(), $agente);

        EncuestaSatisfaccion::create([
            'ticket_id'    => $ticket->id,
            'agente_id'    => $agente->id,
            'usuario_id'   => $ticket->user_id,
            'calificacion' => 2,
            'comentario'   => 'Tuve que explicar el problema tres veces.',
        ]);

        $this->actingAs($agente)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Calificación del solicitante')
            ->assertSee('Mala')
            ->assertSee('Tuve que explicar el problema tres veces.')
            ->assertDontSee('¿Cómo te atendimos?');
    }

    public function test_el_reporte_por_agente_muestra_el_promedio_de_satisfaccion(): void
    {
        $agente = User::factory()->soporte()->create();

        foreach ([5, 4] as $nota) {
            $ticket = $this->ticketCerrado(User::factory()->create(), $agente);
            EncuestaSatisfaccion::create([
                'ticket_id'    => $ticket->id,
                'agente_id'    => $agente->id,
                'usuario_id'   => $ticket->user_id,
                'calificacion' => $nota,
            ]);
        }

        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.reports.agents'))
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('2 respuestas');
    }
}
