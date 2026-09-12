<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Quién puede ver y hacer qué.
 *
 * Un error acá no se ve nunca desde adentro: la plataforma funciona igual de
 * bien, solo que alguien alcanza algo que no le corresponde. No hay pantalla
 * de error que lo delate y nadie lo reporta, porque quien lo descubre no
 * suele avisar.
 *
 * Los tres roles son 'user' (quien pide ayuda), 'support' (quien la da) y
 * 'admin' (quien además configura).
 */
class PermisosPorRolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function ticketDe(?User $dueno, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'TK-' . fake()->unique()->numerify('##########'),
            'user_id'       => $dueno?->id,
            'title'         => 'El correo no me llega',
            'description'   => 'Descripcion de prueba.',
            'status'        => Ticket::STATUS_OPEN,
            'priority'      => 'medium',
        ], $extra));
    }

    // ── Ver un ticket ─────────────────────────────────────────────────

    public function test_un_usuario_no_puede_ver_el_ticket_de_otro(): void
    {
        $ajeno  = $this->ticketDe(User::factory()->create());
        $curioso = User::factory()->create();

        $this->actingAs($curioso)
            ->get(route('tickets.show', $ajeno))
            ->assertForbidden();
    }

    public function test_un_usuario_puede_ver_su_propio_ticket(): void
    {
        $dueno  = User::factory()->create();
        $ticket = $this->ticketDe($dueno);

        $this->actingAs($dueno)
            ->get(route('tickets.show', $ticket))
            ->assertOk();
    }

    public function test_un_usuario_no_puede_ver_el_ticket_de_un_invitado(): void
    {
        // Un ticket de invitado tiene user_id nulo. Si la comparación de
        // "soy el dueño" se hiciera de forma laxa, null contra el id podría
        // dar verdadero y cualquiera vería todos los tickets de invitados.
        $invitado = $this->ticketDe(null, [
            'guest_email' => 'alguien@ejemplo.cl',
            'guest_token' => str_repeat('c', 40),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('tickets.show', $invitado))
            ->assertForbidden();
    }

    public function test_soporte_puede_ver_cualquier_ticket(): void
    {
        $ajeno = $this->ticketDe(User::factory()->create());

        $this->actingAs(User::factory()->soporte()->create())
            ->get(route('tickets.show', $ajeno))
            ->assertOk();
    }

    public function test_un_administrador_puede_ver_cualquier_ticket(): void
    {
        $ajeno = $this->ticketDe(User::factory()->create());

        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('tickets.show', $ajeno))
            ->assertOk();
    }

    public function test_el_panel_lateral_respeta_los_mismos_permisos(): void
    {
        // Es otra ruta que devuelve el mismo contenido sin el layout. Si se
        // olvidara la comprobación ahí, sería una puerta de atrás a lo mismo.
        $ajeno = $this->ticketDe(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('tickets.panel', $ajeno))
            ->assertForbidden();
    }

    // ── El listado ────────────────────────────────────────────────────

    public function test_el_listado_de_un_usuario_solo_muestra_sus_tickets(): void
    {
        $dueno = User::factory()->create();
        $mio   = $this->ticketDe($dueno, ['title' => 'Mi problema con la impresora']);
        $ajeno = $this->ticketDe(User::factory()->create(), ['title' => 'Problema ajeno confidencial']);

        $this->actingAs($dueno)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($mio->ticket_number)
            ->assertDontSee($ajeno->ticket_number);
    }

    public function test_el_listado_de_soporte_muestra_los_tickets_de_todos(): void
    {
        $uno = $this->ticketDe(User::factory()->create());
        $dos = $this->ticketDe(User::factory()->create());

        $this->actingAs(User::factory()->soporte()->create())
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee($uno->ticket_number)
            ->assertSee($dos->ticket_number);
    }

    // ── La zona de administración ─────────────────────────────────────

    public function test_un_usuario_comun_no_entra_a_la_administracion(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_soporte_tampoco_entra_a_la_administracion(): void
    {
        // Atender tickets y configurar la plataforma son cosas distintas.
        $this->actingAs(User::factory()->soporte()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_el_administrador_si_entra(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_sin_sesion_la_administracion_manda_al_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    // ── Acciones sobre el ticket ──────────────────────────────────────

    public function test_un_usuario_no_puede_asignarle_su_ticket_a_un_agente(): void
    {
        $dueno  = User::factory()->create();
        $ticket = $this->ticketDe($dueno);
        $agente = User::factory()->soporte()->create();

        $this->actingAs($dueno)
            ->post(route('tickets.assignTo', $ticket), ['assigned_to' => $agente->id]);

        $this->assertNull($ticket->fresh()->assigned_to, 'el solicitante no reparte el trabajo del equipo');
    }

    public function test_un_usuario_no_puede_subirle_la_prioridad_a_su_ticket(): void
    {
        // Si pudiera, todos los tickets serían críticos y la prioridad dejaría
        // de servir para ordenar el trabajo.
        $dueno  = User::factory()->create();
        $ticket = $this->ticketDe($dueno, ['priority' => 'low']);

        $this->actingAs($dueno)
            ->put(route('tickets.updatePriority', $ticket), ['priority' => 'critical']);

        $this->assertSame('low', $ticket->fresh()->priority);
    }

    public function test_un_usuario_no_puede_derivar_su_ticket_a_otro_departamento(): void
    {
        $dueno  = User::factory()->create();
        $ticket = $this->ticketDe($dueno);

        $this->actingAs($dueno)
            ->post(route('tickets.forward', $ticket), ['department_id' => 1]);

        $this->assertSame(Ticket::STATUS_OPEN, $ticket->fresh()->status);
    }

    public function test_un_usuario_no_puede_tocar_el_ticket_de_otro(): void
    {
        $ajeno   = $this->ticketDe(User::factory()->create());
        $curioso = User::factory()->create();

        $this->actingAs($curioso)
            ->put(route('tickets.updateStatus', $ajeno), ['status' => 'closed'])
            ->assertForbidden();

        $this->assertSame(Ticket::STATUS_OPEN, $ajeno->fresh()->status);
    }

    public function test_un_usuario_no_puede_cambiar_el_estado_de_su_propio_ticket(): void
    {
        // El solicitante confirma una resolución mediante tickets.close;
        // el selector de estado queda reservado para soporte.
        $dueno  = User::factory()->create();
        $ticket = $this->ticketDe($dueno);

        $this->actingAs($dueno)
            ->put(route('tickets.updateStatus', $ticket), ['status' => 'resolved'])
            ->assertForbidden();

        $this->assertSame(Ticket::STATUS_OPEN, $ticket->fresh()->status);
    }

    public function test_un_agente_asignado_si_puede_cambiar_la_prioridad(): void
    {
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticketDe(User::factory()->create(), [
            'priority'    => 'low',
            'assigned_to' => $agente->id,
        ]);

        $this->actingAs($agente)
            ->put(route('tickets.updatePriority', $ticket), ['priority' => 'high']);

        $this->assertSame('high', $ticket->fresh()->priority);
    }
}
