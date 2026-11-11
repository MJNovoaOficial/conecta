<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardIndicatorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(15, 0));
        config(['project_log.enabled' => false]);
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'DASH-'.Str::uuid(),
            'title' => 'Caso de prueba',
            'description' => 'Datos ficticios para comprobar los indicadores.',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'medium',
            'guest_name' => 'Solicitante de prueba',
            'guest_email' => 'dashboard@example.invalid',
            'created_at' => now()->subDay(),
        ], $attributes));
    }

    public function test_workload_keeps_every_technician_paired_with_their_actual_active_tickets(): void
    {
        $admin = User::factory()->administrador()->create(['name' => 'Administrador']);
        $agents = User::factory()->soporte()->count(12)->create();
        User::factory()->soporte()->desactivado()->create();
        User::factory()->create();
        $this->ticket(['assigned_to' => $agents[0]->id, 'status' => 'forwarded']);
        $this->ticket(['assigned_to' => $agents[0]->id, 'status' => 'pending_user', 'created_at' => now()->subYear()]);
        $this->ticket(['assigned_to' => $agents[0]->id, 'status' => 'closed']);
        $this->ticket(['assigned_to' => $agents[1]->id, 'status' => 'in_progress']);
        $this->ticket(['assigned_to' => $admin->id, 'status' => 'open']);
        $before = Ticket::orderBy('id')->get()->map->getAttributes()->all();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $actual = $response->viewData('byAgent');
        $this->assertCount(13, $actual);
        $this->assertSame($agents[0]->id, $actual->first()->id);
        $this->assertSame(2, $actual->first()->active_count);
        $this->assertSame(1, $actual->firstWhere('id', $agents[1]->id)->active_count);
        $this->assertSame(0, $actual->firstWhere('id', $agents[11]->id)->active_count);
        $response->assertSee('autoSkip: false', false)->assertSee('maintainAspectRatio: false', false);
        $this->assertSame($before, Ticket::orderBy('id')->get()->map->getAttributes()->all());
    }

    public function test_manager_shows_all_technicians_and_includes_pending_and_forwarded_critical_cases(): void
    {
        $admin = User::factory()->administrador()->create();
        $agents = User::factory()->soporte()->count(8)->sequence(
            fn ($sequence) => ['name' => 'Tecnico '.str_pad((string) $sequence->index, 2, '0', STR_PAD_LEFT)]
        )->create();
        $this->ticket(['assigned_to' => $agents[7]->id, 'status' => 'forwarded', 'priority' => 'critical']);
        $this->ticket(['status' => 'pending_user', 'priority' => 'critical']);
        $this->ticket(['status' => 'resolved', 'priority' => 'critical']);
        $response = $this->actingAs($admin)->get(route('admin.gerencial'))->assertOk();
        $response->assertViewHas('openTickets', 2)->assertViewHas('criticalOpen', 2)->assertSee('Tecnico 07');
        $this->assertCount(9, $response->viewData('agentPerformance'));
        $this->assertSame(1, $response->viewData('agentPerformance')->firstWhere('id', $agents[7]->id)->total_active);
    }

    public function test_resolution_keeps_minutes_and_sla_only_evaluates_tickets_with_a_deadline_in_the_period(): void
    {
        $admin = User::factory()->administrador()->create();
        foreach ([[15, 30], [45, 30], [30, null]] as [$minutes, $deadline]) {
            $this->ticket([
                'status' => 'resolved', 'created_at' => now()->subHours(2),
                'resolved_at' => now()->subHours(2)->addMinutes($minutes),
                'sla_resolution_deadline_at' => $deadline === null ? null : now()->subHours(2)->addMinutes($deadline),
            ]);
        }
        $this->ticket([
            'status' => 'closed', 'created_at' => now()->subDays(60),
            'resolved_at' => now()->subDays(60)->addHours(20),
            'sla_resolution_deadline_at' => now()->subDays(60)->addHour(),
        ]);
        foreach (['admin.dashboard', 'admin.gerencial'] as $route) {
            $response = $this->actingAs($admin)->get(route($route, ['period' => '7d']))->assertOk();
            $this->assertEquals(0.5, $response->viewData('avgResolutionHours'));
            $this->assertEquals(50, $response->viewData('slaCompliance'));
        }
        $response->assertViewHas('totalResolved', 2)->assertViewHas('slaMissed', 1);
    }

    public function test_categories_and_priorities_count_tickets_and_period_filters_keep_current_workload(): void
    {
        $admin = User::factory()->administrador()->create();
        $requester = User::factory()->create();
        $category = Categoria::create(['name' => 'Redes']);
        $subcategories = collect(['Correo', 'Conectividad'])->map(
            fn ($name) => Subcategoria::create(['name' => $name, 'categoria_id' => $category->id])
        );
        foreach (range(1, 3) as $index) {
            $this->ticket(['subcategoria_id' => $subcategories[$index % 2]->id, 'user_id' => $requester->id, 'priority' => 'high']);
        }
        $this->ticket(['subcategoria_id' => $subcategories[0]->id, 'created_at' => now()->subDays(60)]);
        foreach (['admin.dashboard', 'admin.gerencial'] as $route) {
            $response = $this->actingAs($admin)->get(route($route, ['period' => '7d']))->assertOk();
            $this->assertSame(3, (int) $response->viewData('byPriority')['high']);
            $this->assertSame(3, (int) ($route === 'admin.dashboard'
                ? $response->viewData('byCategory')['Redes']
                : $response->viewData('byCategory')->firstWhere('id', $category->id)->ticket_count));
        }
        $response->assertViewHas('totalTickets', 3)->assertViewHas('openTickets', 4);
        $this->assertSame(3, $response->viewData('topRequesters')->first()->ticket_count);
    }

    public function test_six_month_trend_has_distinct_months_at_month_end_and_renders_real_zeroes(): void
    {
        $this->travelTo(now()->setDate(2026, 3, 31)->setTime(15, 0));
        $admin = User::factory()->administrador()->create();
        foreach (['2025-10-10', '2025-12-10', '2026-02-10', '2026-03-10'] as $date) {
            $this->ticket(['created_at' => $date.' 12:00:00']);
        }
        $response = $this->actingAs($admin)->get(route('admin.gerencial'))->assertOk();
        $this->assertSame([1, 0, 1, 0, 1, 1], array_map('intval', $response->viewData('trendValues')));
        $this->assertCount(6, array_unique($response->viewData('trendLabels')));
        $response->assertSee('class="trend-bar" style="height:0px;"', false);
    }

    public function test_empty_dashboards_render_and_missing_sla_remains_unavailable(): void
    {
        $admin = User::factory()->administrador()->create();
        foreach (['admin.dashboard', 'admin.gerencial'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk()
                ->assertViewHas('totalTickets', 0)->assertViewHas('slaCompliance', fn ($value) => $value === null)
                ->assertViewHas('avgResolutionHours', fn ($value) => $value === null);
        }
        $this->ticket(['status' => 'resolved', 'resolved_at' => now()]);
        foreach (['admin.dashboard', 'admin.gerencial'] as $route) {
            $this->get(route($route))->assertOk()->assertViewHas('slaCompliance', fn ($value) => $value === null);
        }
    }
}
