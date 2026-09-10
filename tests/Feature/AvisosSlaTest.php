<?php

namespace Tests\Feature;

use App\Jobs\SendSlaWarningsJob;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SlaWarningNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Avisos antes de que venza el plazo de resolución.
 *
 * La parte delicada no es avisar, es avisar UNA vez. Este trabajo corre cada
 * pocos minutos, así que un error en la condición de "ya avisé" no se nota en
 * una prueba manual: se nota cuando el agente recibe el mismo correo veinte
 * veces y deja de leerlos.
 *
 * De hecho la primera versión guardaba CUÁNDO se avisó y lo comparaba contra
 * el plazo. Como el plazo siempre está en el futuro, la comparación daba
 * verdadero siempre y el aviso se repetía en cada pasada. Ahora se guarda de
 * QUÉ plazo se avisó, y esa diferencia es la que prueban estos casos.
 */
class AvisosSlaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config(['sla.aviso_minutos_antes' => 30]);
    }

    private function ticket(array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number'              => 'TK-' . fake()->unique()->numerify('##########'),
            'title'                      => 'El sistema no responde',
            'description'                => 'Descripcion de prueba.',
            'status'                     => Ticket::STATUS_IN_PROGRESS,
            'priority'                   => 'high',
            // Dentro de la ventana de aviso: quedan 20 minutos.
            'sla_resolution_deadline_at' => now()->addMinutes(20),
        ], $extra));
    }

    // ── Cuándo avisa ──────────────────────────────────────────────────

    public function test_avisa_cuando_el_plazo_esta_por_vencer(): void
    {
        $agente = User::factory()->soporte()->create();
        $this->ticket(['assigned_to' => $agente->id]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(1, Notificacion::where('type', 'sla_warning')->count());
        Notification::assertSentTo($agente, SlaWarningNotification::class);
    }

    public function test_no_avisa_de_un_plazo_todavia_lejano(): void
    {
        $agente = User::factory()->soporte()->create();
        $this->ticket([
            'assigned_to'                => $agente->id,
            'sla_resolution_deadline_at' => now()->addHours(5),
        ]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(0, Notificacion::count());
        Notification::assertNothingSent();
    }

    public function test_no_avisa_de_un_plazo_que_ya_vencio(): void
    {
        // Ya no es un aviso, es una constatación. Para eso está el semáforo
        // del listado, no un correo.
        $agente = User::factory()->soporte()->create();
        $this->ticket([
            'assigned_to'                => $agente->id,
            'sla_resolution_deadline_at' => now()->subHour(),
        ]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(0, Notificacion::count());
    }

    public function test_no_avisa_de_un_ticket_que_espera_al_solicitante(): void
    {
        // En "Pendiente Usuario" la siguiente acción no es de soporte. Avisarle
        // al agente de un plazo que no depende de él es ruido, y para ese caso
        // ya existe el cierre automático por falta de respuesta.
        $agente = User::factory()->soporte()->create();
        $this->ticket([
            'assigned_to' => $agente->id,
            'status'      => Ticket::STATUS_PENDING_USER,
        ]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(0, Notificacion::count());
    }

    public function test_no_avisa_de_tickets_ya_resueltos_o_cerrados(): void
    {
        $agente = User::factory()->soporte()->create();

        foreach ([Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED] as $estado) {
            $this->ticket(['assigned_to' => $agente->id, 'status' => $estado]);
        }

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(0, Notificacion::count());
    }

    // ── Que avise una sola vez ────────────────────────────────────────

    public function test_no_repite_el_aviso_del_mismo_plazo(): void
    {
        // Este es el caso que la primera versión hacía mal. El trabajo corre
        // cada pocos minutos: si no deduplica, son decenas de correos por
        // ticket y el agente deja de mirarlos.
        $agente = User::factory()->soporte()->create();
        $this->ticket(['assigned_to' => $agente->id]);

        (new SendSlaWarningsJob)->handle();
        (new SendSlaWarningsJob)->handle();
        (new SendSlaWarningsJob)->handle();

        $this->assertSame(
            1,
            Notificacion::where('type', 'sla_warning')->count(),
            'tres pasadas del trabajo deben producir un solo aviso'
        );
        Notification::assertSentToTimes($agente, SlaWarningNotification::class, 1);
    }

    public function test_vuelve_a_avisar_si_le_cambian_el_plazo(): void
    {
        // Y esta es la razón de guardar DE QUÉ plazo se avisó y no cuándo: si
        // a un ticket le corren la fecha, merece un aviso nuevo.
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticket(['assigned_to' => $agente->id]);

        (new SendSlaWarningsJob)->handle();

        $ticket->update(['sla_resolution_deadline_at' => now()->addMinutes(25)]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(2, Notificacion::where('type', 'sla_warning')->count());
    }

    public function test_la_marca_guarda_el_plazo_del_que_se_aviso(): void
    {
        $agente = User::factory()->soporte()->create();
        $ticket = $this->ticket(['assigned_to' => $agente->id]);

        (new SendSlaWarningsJob)->handle();

        $ticket->refresh();

        $this->assertNotNull($ticket->sla_warned_for);
        $this->assertSame(
            $ticket->sla_resolution_deadline_at->format('Y-m-d H:i:s'),
            $ticket->sla_warned_for->format('Y-m-d H:i:s'),
            'la marca debe ser el plazo avisado, no el momento del aviso'
        );
    }

    // ── A quién avisa ─────────────────────────────────────────────────

    public function test_avisa_solo_al_agente_asignado(): void
    {
        $agente = User::factory()->soporte()->create();
        User::factory()->soporte()->create();      // otro del equipo
        User::factory()->administrador()->create();

        $this->ticket(['assigned_to' => $agente->id]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(1, Notificacion::count());
        $this->assertSame($agente->id, Notificacion::first()->user_id);
    }

    public function test_si_el_ticket_no_tiene_dueno_avisa_a_todo_el_equipo(): void
    {
        // Un ticket sin asignar cerca del vencimiento es el que más riesgo
        // corre, justamente porque nadie lo está mirando.
        $soporte = User::factory()->soporte()->count(2)->create();
        $admin   = User::factory()->administrador()->create();
        User::factory()->count(3)->create();                    // usuarios comunes
        User::factory()->soporte()->desactivado()->create();    // ya no trabaja aquí

        $this->ticket(['assigned_to' => null]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(3, Notificacion::count(), 'los dos de soporte y el administrador');

        $avisados = Notificacion::pluck('user_id')->sort()->values()->all();
        $esperados = $soporte->pluck('id')->push($admin->id)->sort()->values()->all();

        $this->assertSame($esperados, $avisados);
    }

    public function test_una_cuenta_desactivada_no_recibe_avisos(): void
    {
        $inactivo = User::factory()->soporte()->desactivado()->create();
        User::factory()->soporte()->create();

        $this->ticket(['assigned_to' => null]);

        (new SendSlaWarningsJob)->handle();

        $this->assertSame(
            0,
            Notificacion::where('user_id', $inactivo->id)->count(),
            'quien ya no trabaja aquí no debería seguir recibiendo avisos'
        );
    }
}
