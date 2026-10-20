<?php

namespace Tests\Feature;

use App\Jobs\CloseResolvedTicketsJob;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketDelegation;
use App\Models\User;
use App\Support\VersionedAsset;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Regression against the published timezone and access-cache improvements. */
class TicketDelegationCurrentCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private function scenario(): array
    {
        config(['ticket_delegation.enabled' => true]);
        Mail::fake();
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-10-20 13:39:00', 'UTC'));
        $ti = Department::create(['name' => 'TI', 'is_active' => true]);
        $other = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $agent = User::factory()->soporte()->create(['department_id' => $ti->id]);
        $delegate = User::factory()->create(['department_id' => $other->id]);
        $owner = User::factory()->create(['department_id' => $other->id]);
        $ticket = Ticket::create([
            'ticket_number' => 'CURRENT-'.fake()->unique()->numerify('########'),
            'user_id' => $owner->id, 'assigned_to' => $agent->id, 'department_id' => $other->id,
            'title' => 'Caso conservado', 'description' => 'No cambiar información anterior.',
            'status' => 'in_progress', 'priority' => 'medium', 'created_at' => '2026-10-20 13:15:00',
            'sla_resolution_deadline_at' => '2026-10-23 18:00:00', 'reopened_count' => 0,
        ])->fresh();
        return compact('agent', 'delegate', 'owner', 'ticket');
    }

    private function grant(array $context): TicketDelegation
    {
        $this->actingAs($context['agent'])->post(route('tickets.delegations.store', $context['ticket']), [
            'delegate_id' => $context['delegate']->id, 'instructions' => 'Revisar este caso específico y proponer una solución.',
            'expected_id' => 0, 'version' => 0,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        return TicketDelegation::sole();
    }

    public function test_recorrido_completo_conserva_hora_chilena_css_actual_y_cierre_exactamente_una_hora_despues(): void
    {
        $context = $this->scenario();
        extract($context);
        $users = DB::table('usuarios')->orderBy('id')->get()->toJson();
        $stable = $ticket->only(['user_id', 'assigned_to', 'department_id', 'description', 'priority', 'created_at', 'sla_resolution_deadline_at']);
        $delegation = $this->grant($context);
        $this->actingAs($delegate)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Información pública de la revisión.', 'delegation_id' => $delegation->id,
            'delegation_version' => 1,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $response = $this->get(route('tickets.show', $ticket))->assertOk()
            ->assertSee('20/10/2026 10:15')->assertSee('20/10/2026 10:39')
            ->assertDontSee('data-auth-form-freshness', false);
        foreach (['css/premium.css', 'css/responsive.css'] as $path) {
            $response->assertSee(VersionedAsset::url($path), false);
        }
        $this->post(route('tickets.delegations.propose', [$ticket, $delegation]), [
            'version' => 1, 'solution_text' => 'Propuesta revisable que aún no debe ser pública.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->actingAs($owner)->get(route('tickets.show', $ticket))->assertOk()
            ->assertDontSee('Propuesta revisable que aún no debe ser pública.');
        $this->actingAs($agent)->post(route('tickets.delegations.changes', [$ticket, $delegation]), [
            'version' => 2, 'feedback' => 'Comprueba nuevamente el resultado antes de enviar.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($delegate)->post(route('tickets.delegations.propose', [$ticket, $delegation]), [
            'version' => 3, 'solution_text' => 'Solución definitiva verificada y lista para revisión.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($agent)->post(route('tickets.delegations.approve', [$ticket, $delegation]), ['version' => 4])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->actingAs($delegate)->get(route('tickets.show', $ticket))->assertOk()
            ->assertSee('Cierre automático a partir del 20/10/2026 11:39');
        $this->travelTo(Carbon::parse('2026-10-20 14:38:59', 'UTC'));
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->travelTo(Carbon::parse('2026-10-20 14:39:00', 'UTC'));
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertSame('2026-10-20 13:39:00', $ticket->fresh()->getRawOriginal('resolved_at'));
        $this->assertSame('2026-10-20 14:39:00', $ticket->fresh()->getRawOriginal('closed_at'));
        $this->assertSame($users, DB::table('usuarios')->orderBy('id')->get()->toJson());
        $this->assertEquals($stable, $ticket->fresh()->only(array_keys($stable)));
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_filtros_por_dia_local_no_exponen_casos_ajenos_ni_pierden_el_encargo_cerca_de_medianoche(): void
    {
        $context = $this->scenario();
        extract($context);
        $ticket->update(['created_at' => '2026-10-21 02:59:59']); // 20/10 at 23:59 in Chile.
        $this->grant($context);
        $other = $ticket->replicate(['ticket_number']);
        $other->ticket_number = 'AJENO-NO-EXPONER';
        $other->save();
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs($delegate)->get(route('tickets.index', ['date_from' => '2026-10-20', 'date_to' => '2026-10-20']))
            ->assertOk()->assertViewHas('tickets', fn ($tickets) => $tickets->pluck('id')->all() === [$ticket->id])
            ->assertDontSee('AJENO-NO-EXPONER');
        $this->get(route('tickets.show', $other))->assertForbidden();
        $this->assertSame($before, $ticket->fresh()->getAttributes());
    }

    public static function mutationRoutes(): array
    {
        return array_combine(['store', 'propose', 'changes', 'approve', 'revoke'],
            array_map(fn ($action) => [$action], ['store', 'propose', 'changes', 'approve', 'revoke']));
    }

    #[DataProvider('mutationRoutes')]
    public function test_cada_accion_nueva_conserva_proteccion_csrf_y_no_reutiliza_formularios_vencidos(string $action): void
    {
        $context = $this->scenario();
        $delegation = $this->grant($context);
        $ticket = $context['ticket'];
        $before = $ticket->fresh()->getAttributes();
        $delegationBefore = $delegation->getAttributes();
        $historyCount = $ticket->history()->count();
        config(['app.debug' => false]);
        $this->app['env'] = 'production'; // Real CSRF validation on the allowlisted test database.
        $this->actingAs($action === 'propose' ? $context['delegate'] : $context['agent']);
        $parameters = $action === 'store' ? [$ticket] : [$ticket, $delegation];
        $this->withSession(['_token' => 'actual'])->post(route('tickets.delegations.'.$action, $parameters), [
            '_token' => 'vencido', 'version' => 1, 'expected_id' => $delegation->id,
            'delegate_id' => $context['delegate']->id, 'instructions' => 'No se debe ejecutar esta acción.',
            'feedback' => 'No se debe guardar esta corrección.', 'solution_text' => 'No se debe guardar esta propuesta.',
        ])->assertStatus(419)->assertSee('La página ha expirado');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame($delegationBefore, $delegation->fresh()->getAttributes());
        $this->assertSame($historyCount, $ticket->history()->count());
    }
}
