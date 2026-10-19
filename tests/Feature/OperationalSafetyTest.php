<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\SystemSetting;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OperationalSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
    }

    private function department(): Department
    {
        return Department::create(['name' => 'Atencion', 'is_active' => true, 'default_role' => 'user']);
    }

    private function updateData(User $user, Department $department, array $extra = []): array
    {
        return array_merge(['name' => 'Persona Prueba', 'email' => $user->email, 'role' => $user->role,
            'department_id' => $department->id, 'is_active' => 1], $extra);
    }

    private function ticket(User $agent, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'SAFE-'.fake()->unique()->numerify('########'), 'user_id' => User::factory()->create()->id,
            'assigned_to' => $agent->id, 'title' => 'Caso conservado', 'description' => 'No modificar.',
            'status' => Ticket::STATUS_IN_PROGRESS, 'priority' => 'medium',
        ], $extra))->fresh();
    }

    public function test_ultimo_admin_no_puede_perder_su_rol_y_no_se_altera_ningun_dato(): void
    {
        $department = $this->department();
        $admin = User::factory()->administrador()->create(['department_id' => $department->id]);
        $before = $admin->fresh()->getAttributes();
        $this->actingAs($admin)->put(route('admin.users.update', $admin), $this->updateData($admin, $department, ['role' => 'user']))->assertSessionHasErrors('role');
        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->assertSame(1, User::where('role', 'admin')->where('is_active', true)->count());
        $this->get(route('admin.users.index'))->assertOk();
    }

    public function test_admin_inactivo_no_cuenta_como_respaldo_y_otro_activo_si_permite_transferir_el_rol(): void
    {
        $department = $this->department();
        $admin = User::factory()->administrador()->create(['department_id' => $department->id]);
        User::factory()->administrador()->create(['is_active' => false]);
        $this->actingAs($admin)->put(route('admin.users.update', $admin), $this->updateData($admin, $department, ['role' => 'user']))->assertSessionHasErrors('role');
        $other = User::factory()->administrador()->create();
        $this->put(route('admin.users.update', $admin), $this->updateData($admin, $department, ['role' => 'user']))->assertSessionHas('success');
        $this->assertSame('user', $admin->fresh()->role);
        $this->assertTrue($other->fresh()->is_active);
        $this->assertSame(3, User::count());
    }

    public function test_agente_con_casos_activos_debe_transferirlos_antes_de_perder_acceso(): void
    {
        $department = $this->department();
        $admin = User::factory()->administrador()->create();
        $agent = User::factory()->soporte()->create(['department_id' => $department->id]);
        foreach ([Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING_USER, Ticket::STATUS_FORWARDED] as $status) {
            $ticket = $this->ticket($agent, ['status' => $status]);
            $before = $ticket->getAttributes();
            $beforeUser = $agent->fresh()->getAttributes();
            $this->actingAs($admin)->put(route('admin.users.update', $agent), $this->updateData($agent, $department, ['is_active' => 0]))->assertSessionHasErrors('is_active');
            $this->put(route('admin.users.update', $agent), $this->updateData($agent, $department, ['role' => 'user']))->assertSessionHasErrors('role');
            $this->assertSame($before, $ticket->fresh()->getAttributes());
            $this->assertSame($beforeUser, $agent->fresh()->getAttributes());
        }
        $next = User::factory()->soporte()->create();
        foreach (Ticket::where('assigned_to', $agent->id)->get() as $ticket) {
            $this->actingAs($agent)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])->assertSessionHas('success');
        }
        $this->actingAs($admin)->put(route('admin.users.update', $agent), $this->updateData($agent, $department, ['is_active' => 0]))->assertSessionHas('success');
        $this->assertFalse($agent->fresh()->is_active);
        $this->assertSame(4, Ticket::where('assigned_to', $next->id)->count());
    }

    public function test_un_resuelto_reabrible_impide_retirar_acceso_hasta_cerrar_y_no_se_modifica_el_caso(): void
    {
        $department = $this->department();
        $admin = User::factory()->administrador()->create();
        $agent = User::factory()->soporte()->create(['department_id' => $department->id]);
        $tickets = [];
        foreach ([Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED] as $status) {
            $ticket = $this->ticket($agent, ['status' => $status]);
            $tickets[$ticket->id] = $ticket->getAttributes();
        }
        $this->actingAs($admin)->put(route('admin.users.update', $agent), $this->updateData($agent, $department, ['is_active' => 0]))->assertSessionHasErrors('is_active');
        $this->put(route('admin.users.update', $agent), $this->updateData($agent, $department, ['role' => 'user']))->assertSessionHasErrors('role');
        foreach ($tickets as $id => $before) $this->assertSame($before, Ticket::findOrFail($id)->getAttributes());
        $this->assertTrue($agent->fresh()->is_active);
        // El cierre final hace que ya no exista la posibilidad de reapertura.
        Ticket::where('assigned_to', $agent->id)->update(['status' => Ticket::STATUS_CLOSED]);
        foreach (array_keys($tickets) as $id) $tickets[$id] = Ticket::findOrFail($id)->getAttributes();
        $this->actingAs($admin)->put(route('admin.users.update', $agent), $this->updateData($agent, $department, ['is_active' => 0]))->assertSessionHas('success');
        foreach ($tickets as $id => $before) $this->assertSame($before, Ticket::findOrFail($id)->getAttributes());
    }

    public function test_responsable_historico_inactivo_no_habilita_chat_y_se_puede_reactivar_sin_tocar_ticket(): void
    {
        $department = $this->department();
        $agent = User::factory()->soporte()->create(['is_active' => false]);
        $ticket = $this->ticket($agent);
        $before = $ticket->getAttributes();
        $this->assertFalse($ticket->hasAssignedSupport());
        $this->actingAs($ticket->user)->post(route('tickets.addComment', $ticket), ['comment' => 'No hay agente activo.'])->assertForbidden();
        $this->actingAs(User::factory()->administrador()->create())->put(route('admin.users.update', $agent), $this->updateData($agent, $department))->assertSessionHas('success');
        $this->assertTrue($ticket->fresh()->hasAssignedSupport());
        $this->assertSame($before, $ticket->fresh()->getAttributes());
    }

    public function test_departamentos_guardan_texto_sin_doble_escape_y_no_reescriben_historicos(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        $text = "D'Operaciones & soporte <script>alert(1)</script>";
        $this->post(route('admin.departments.store'), ['name' => 'Operaciones', 'description' => $text, 'default_role' => 'user'])->assertSessionHas('success');
        $department = Department::where('name', 'Operaciones')->sole();
        $this->assertSame($text, $department->description);
        $this->get(route('admin.departments.index'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->put(route('admin.departments.update', $department), ['name' => $department->name, 'description' => $text, 'default_role' => 'user', 'is_active' => 1])->assertSessionHas('success');
        $this->assertSame($text, $department->fresh()->description);
        $legacy = Department::create(['name' => 'Historico', 'description' => 'D&#039;Area &amp; equipo', 'default_role' => 'user', 'is_active' => true])->fresh();
        $before = $legacy->getAttributes();
        $this->get(route('admin.departments.index'))->assertOk();
        $this->assertSame($before, $legacy->fresh()->getAttributes());
    }

    public function test_etiquetas_personalizadas_se_reflejan_sin_cambiar_estados_y_cache_se_invalida(): void
    {
        $ticket = $this->ticket(User::factory()->soporte()->create(), ['status' => Ticket::STATUS_OPEN]);
        $before = $ticket->getAttributes();
        $this->assertSame('Abierto', $ticket->getStatusLabel());
        $this->assertDatabaseCount('system_settings', 0);
        $admin = User::factory()->administrador()->create();
        $label = "Ingresado 'TI' <img src=x onerror=alert(1)>";
        $this->actingAs($admin)->post(route('admin.states.update'), ['labels' => ['open' => $label]])->assertSessionHas('success');
        $this->assertSame($label, $ticket->getStatusLabel());
        foreach (['tickets.show', 'tickets.panel', 'tickets.index', 'admin.reports.index'] as $route) {
            $params = in_array($route, ['tickets.show', 'tickets.panel'], true) ? [$ticket] : [];
            $this->get(route($route, $params))->assertOk()->assertSee($label)->assertDontSee('<img src=x onerror=alert(1)>', false);
        }
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        SystemSetting::set('state_label_open', 'Nuevo ingreso');
        $this->assertSame('Nuevo ingreso', $ticket->getStatusLabel());
        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertSame('Cerrado', Ticket::statusLabels()['closed']);
    }

    public function test_filtro_periodo_invalido_devuelve_validacion_sin_bucle_y_los_validos_siguen_funcionando(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        foreach (['admin.dashboard', 'admin.gerencial'] as $route) {
            $badUrl = route($route, ['period' => ['7d']]);
            $this->getJson($badUrl)->assertUnprocessable()->assertJsonValidationErrors('period');
            $this->from($badUrl)->get($badUrl)->assertRedirect(route($route))->assertSessionHasErrors('period');
            $this->get(route($route, ['period' => '7d']))->assertOk();
            $this->get(route($route, ['period' => 'desconocido']))->assertOk();
        }
    }

    public function test_rutas_residuales_no_publican_edicion_o_borrado_inexistentes_y_conservan_registros(): void
    {
        $ticket = $this->ticket(User::factory()->soporte()->create());
        $before = $ticket->getAttributes();
        $this->assertFalse(Route::has('tickets.edit'));
        $this->assertFalse(Route::has('tickets.update'));
        $this->assertFalse(Route::has('tickets.destroy'));
        $this->actingAs($ticket->user)->get('/tickets/'.$ticket->id.'/edit')->assertNotFound();
        $this->put('/tickets/'.$ticket->id, ['title' => 'No cambiar'])->assertStatus(405);
        $this->delete('/tickets/'.$ticket->id)->assertStatus(405);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
    }

    public function test_token_guest_con_sesion_normal_conserva_respuesta_reapertura_y_encuesta_sin_vincular_cuenta(): void
    {
        $viewer = User::factory()->create();
        $ticket = $this->ticket(User::factory()->soporte()->create(), ['user_id' => null,
            'guest_name' => 'Invitado', 'guest_email' => $viewer->email, 'guest_token' => str_repeat('v', 40), 'status' => Ticket::STATUS_PENDING_USER]);
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $ticket->assigned_to, 'comment' => 'Privado de TI.', 'is_internal' => true]);
        $this->actingAs($viewer)->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()
            ->assertSee(route('tickets.guest.comment', $ticket->guest_token), false)->assertDontSee('Privado de TI.');
        $this->post(route('tickets.guest.comment', $ticket->guest_token), ['comment' => 'Respuesta usando mi enlace.'])->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->user_id);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->get(route('tickets.show', $ticket))->assertForbidden();
        $this->get(route('tickets.guest.show', str_repeat('x', 40)))->assertNotFound();
        $ticket->update(['status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now(), 'solution_text' => 'Configuración corregida y verificada.']);
        $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()->assertSee(route('tickets.guest.reopen', $ticket->guest_token), false);
        $this->post(route('tickets.guest.reopen', $ticket->guest_token), ['motivo' => 'Todavía tengo problemas al enviar.'])->assertSessionHas('success');
        $ticket->update(['status' => Ticket::STATUS_CLOSED]);
        $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()->assertSee(route('encuesta.invitado', $ticket->guest_token), false);
        $this->post(route('encuesta.invitado', $ticket->guest_token), ['calificacion' => 5])->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->user_id);
        $this->assertNull($ticket->encuesta()->sole()->usuario_id);
    }
}
