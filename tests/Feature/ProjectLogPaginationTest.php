<?php

namespace Tests\Feature;

use App\Models\ProjectLogEntry;
use App\Models\ProjectLogProject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectLogPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_month_remains_fixed_when_following_pagination_after_month_changes(): void
    {
        config(['project_log.enabled' => true]);
        $this->travelTo(Carbon::create(2026, 10, 31, 12, 0, 0, 'UTC'));
        $user = User::factory()->soporte()->create();
        $project = ProjectLogProject::create(['name' => 'Proyecto mensual']);
        for ($i = 1; $i <= 16; $i++) {
            ProjectLogEntry::create([
                'project_id' => $project->id,
                'author_id' => $user->id,
                'author_name' => $user->name,
                'responsible_id' => $user->id,
                'responsible_name' => $user->name,
                'title' => 'Trabajo de octubre '.$i,
                'description' => 'Trabajo realizado durante octubre.',
                'type' => 'improvement',
                'performed_on' => '2026-10-31',
                'submission_key' => (string) Str::uuid(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('project-log.index'))->assertOk();
        $entries = $response->viewData('entries');
        $this->assertSame(16, $entries->total());
        $this->assertSame(15, $entries->count());
        $next = $entries->nextPageUrl();
        $this->assertNotNull($next);
        parse_str(parse_url($next, PHP_URL_QUERY), $query);
        $this->assertSame('2026-10-01', $query['date_from']);
        $this->assertSame('2026-10-31', $query['date_to']);
        $this->assertSame('2', $query['page']);

        $this->travelTo(Carbon::create(2026, 11, 1, 12, 0, 0, 'UTC'));
        // Simular sesión renovada: la inactividad de un día sí debe expirar.
        $this->withSession(['last_activity_at' => now()]);
        $following = $this->get($next)->assertOk();
        $this->assertSame(16, $following->viewData('entries')->total());
        $this->assertSame(1, $following->viewData('entries')->count());
        $this->assertSame('2026-10-01', $following->viewData('filters')['date_from']);
        $this->assertSame('2026-10-31', $following->viewData('filters')['date_to']);
        $this->travelBack();
    }
}
