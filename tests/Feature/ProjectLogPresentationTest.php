<?php

namespace Tests\Feature;

use App\Models\ProjectLogEntry;
use App\Models\ProjectLogProject;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectLogPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['project_log.enabled' => true]);
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(15, 0));
    }

    private function entry(User $author, string $date = '2026-10-20', string $type = 'improvement', ?ProjectLogProject $project = null): ProjectLogEntry
    {
        $project ??= ProjectLogProject::create(['name' => 'Proyecto '.Str::uuid()]);
        return ProjectLogEntry::create([
            'project_id' => $project->id, 'author_id' => $author->id, 'author_name' => $author->name,
            'responsible_id' => $author->id, 'responsible_name' => $author->name, 'title' => 'Trabajo de prueba',
            'description' => 'Descripción del trabajo realizado.', 'type' => $type, 'performed_on' => $date,
            'submission_key' => (string) Str::uuid(),
        ]);
    }

    private function attachment(ProjectLogEntry $entry, UploadedFile $file, ?string $name = null)
    {
        $path = $file->storeAs('project-log/'.$entry->id, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
        return $entry->attachments()->create(['path' => $path, 'original_name' => $name ?? $file->getClientOriginalName(), 'size' => $file->getSize()]);
    }

    public function test_images_are_served_inline_with_real_mime_and_private_headers_without_changing_files(): void
    {
        $staff = User::factory()->soporte()->create();
        $entry = $this->entry($staff);
        $this->actingAs($staff);
        foreach (['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'] as $ext => $mime) {
            $image = $this->attachment($entry, UploadedFile::fake()->image('Foto.'.$ext));
            $before = Storage::disk('local')->get($image->path);
            $response = $this->get(route('project-log.image', [$entry, $image]))->assertOk()
                ->assertHeader('Content-Type', $mime)->assertHeader('Content-Disposition', 'inline')->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->assertSame($before, file_get_contents($response->baseResponse->getFile()->getPathname()));
            $this->assertSame($before, Storage::disk('local')->get($image->path));
            $this->get(route('project-log.download', [$entry, $image]))->assertOk()->assertHeader('Content-Type', 'application/octet-stream');
        }
    }

    public function test_preview_rejects_documents_disguised_images_missing_files_and_wrong_entry(): void
    {
        $staff = User::factory()->soporte()->create();
        $entry = $this->entry($staff);
        $this->actingAs($staff);
        foreach (['ataque.jpg' => '<svg onload="alert(1)"></svg>', 'nota.png' => '<?php echo "bad";', 'documento.txt' => 'Texto plano'] as $name => $content) {
            $file = $this->attachment($entry, UploadedFile::fake()->createWithContent($name, $content));
            $this->get(route('project-log.image', [$entry, $file]))->assertNotFound();
            $this->get(route('project-log.download', [$entry, $file]))->assertOk();
        }
        $image = $this->attachment($entry, UploadedFile::fake()->image('Foto.JPG'));
        $this->get(route('project-log.image', [$entry, $image]))->assertOk();
        $other = $this->entry($staff);
        $this->get(route('project-log.image', [$other, $image]))->assertNotFound();
        Storage::disk('local')->delete($image->path);
        $this->get(route('project-log.image', [$entry, $image]))->assertNotFound();
        $this->get(route('project-log.show', $entry))->assertOk()->assertSee('Descargar')->assertSee('data-log-image', false);
    }

    public function test_preview_keeps_existing_role_and_feature_access_rules(): void
    {
        $staff = User::factory()->soporte()->create();
        $entry = $this->entry($staff);
        $image = $this->attachment($entry, UploadedFile::fake()->image('foto.png'));
        $url = route('project-log.image', [$entry, $image]);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->administrador()->create())->get($url)->assertOk();
        $this->actingAs($staff)->get($url)->assertOk();
        config(['project_log.enabled' => false]);
        $this->get($url)->assertNotFound();
        config(['project_log.enabled' => true]);
        $this->actingAs(User::factory()->soporte()->desactivado()->create())->get($url)->assertRedirect();
    }

    public function test_list_and_detail_show_images_in_the_body_and_keep_document_downloads_escaped(): void
    {
        $staff = User::factory()->soporte()->create();
        $entry = $this->entry($staff);
        $image = $this->attachment($entry, UploadedFile::fake()->image('foto.png'), 'Foto <script>alert(1)</script>.png');
        $document = $this->attachment($entry, UploadedFile::fake()->createWithContent('informe.txt', 'Reporte'));
        $this->actingAs($staff);
        foreach ([route('project-log.index'), route('project-log.show', $entry)] as $url) {
            $response = $this->get($url)->assertOk()->assertSee(route('project-log.image', [$entry, $image]), false)
                ->assertSee('Foto <script>alert(1)</script>.png')->assertDontSee('<script>alert(1)</script>', false)
                ->assertSee('loading="lazy"', false)->assertSee('target="_blank" rel="noopener"', false);
            $this->assertSame(1, substr_count($response->getContent(), 'data-log-image'));
            $response->assertDontSee(route('project-log.image', [$entry, $document]), false);
        }
        $this->get(route('project-log.show', $entry))->assertSee(route('project-log.download', [$entry, $document]), false)->assertSee('Archivos adjuntos');
    }

    public function test_dashboard_counts_distinct_projects_and_work_dates_and_keeps_ticket_kpis(): void
    {
        $admin = User::factory()->administrador()->create();
        $project = ProjectLogProject::create(['name' => 'Proyecto principal']);
        $this->entry($admin, '2026-10-01', 'creation', $project);
        $this->entry($admin, '2026-10-31', 'improvement', $project);
        $this->entry($admin, '2026-09-30', 'correction', $project);
        $this->entry($admin, '2026-10-29', 'correction');
        $this->entry($admin, '2026-01-01', 'improvement');
        $this->entry($admin, '2025-12-31');
        $this->entry($admin, '2027-01-01');
        ProjectLogProject::create(['name' => 'Sin trabajo registrado']);
        $ticket = Ticket::create(['ticket_number' => 'PRESENTATION-'.Str::uuid(), 'user_id' => User::factory()->create()->id,
            'title' => 'Caso conservado', 'description' => 'No modificar.', 'status' => 'open', 'priority' => 'medium',
            'created_at' => now()->subDays(15)])->fresh();
        $before = $ticket->getAttributes();
        $this->actingAs($admin);
        foreach (['7d' => 0, '30d' => 1] as $period => $count) {
            $response = $this->get(route('admin.gerencial', ['period' => $period]))->assertOk()->assertViewHas('totalTickets', $count);
            $summary = $response->viewData('projectLogSummary');
            $this->assertSame(['updates' => 3, 'projects' => 2, 'creation' => 1, 'improvement' => 1, 'correction' => 1], $summary['month']);
            $this->assertSame(['updates' => 5, 'projects' => 3, 'creation' => 1, 'improvement' => 2, 'correction' => 2], $summary['year']);
            $this->assertSame('Proyecto principal', $summary['projects']->first()->name);
            $this->assertSame(2, (int) $summary['projects']->first()->updates);
            $response->assertSee('Actividad de proyectos')->assertSee('Proyectos del mes')->assertSee('Proyectos del año')
                ->assertSee(route('project-log.index', $summary['year_range']));
        }
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('project_log_entries', 7);
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_calendar_uses_local_display_zone_at_month_and_year_boundaries(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->entry($admin, '2026-12-31');
        $this->entry($admin, '2027-01-01');
        $this->actingAs($admin);
        $this->travelTo(\Carbon\CarbonImmutable::parse('2027-01-01 01:00:00', 'UTC'));
        $this->withSession(['last_activity_at' => now()]);
        $summary = $this->get(route('admin.gerencial'))->assertOk()->viewData('projectLogSummary');
        $this->assertSame('2026', $summary['year_label']);
        $this->assertSame('2026-12-01', $summary['month_range']['date_from']);
        $this->assertSame(1, $summary['month']['updates']);
        $this->travelTo(\Carbon\CarbonImmutable::parse('2027-01-01 04:00:00', 'UTC'));
        $this->withSession(['last_activity_at' => now()]);
        $summary = $this->get(route('admin.gerencial'))->assertOk()->viewData('projectLogSummary');
        $this->assertSame('2027', $summary['year_label']);
        $this->assertSame('2027-01-01', $summary['month_range']['date_from']);
        $this->assertSame(1, $summary['year']['updates']);
    }

    public function test_empty_dashboard_renders_zeroes_and_limits_project_detail_without_losing_totals(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin)->get(route('admin.gerencial'))->assertOk()->assertSee('Aún no hay trabajos registrados')
            ->assertViewHas('projectLogSummary', fn ($data) => $data['month']['projects'] === 0 && $data['year']['updates'] === 0);
        foreach (range(1, 6) as $i) $this->entry($admin, '2026-10-20', 'creation', ProjectLogProject::create(['name' => "Proyecto {$i}"]));
        $response = $this->get(route('admin.gerencial'))->assertOk()->assertSee('Se muestran los 5');
        $this->assertSame(6, $response->viewData('projectLogSummary')['month']['projects']);
        $this->assertCount(5, $response->viewData('projectLogSummary')['projects']);
    }

    public function test_dashboard_is_unchanged_when_module_disabled_or_tables_missing_and_does_not_read_records(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin);
        $queries = [];
        DB::listen(function ($query) use (&$queries) { if (str_contains($query->sql, 'project_log_')) $queries[] = $query->sql; });
        config(['project_log.enabled' => false]);
        $this->get(route('admin.gerencial'))->assertOk()->assertViewHas('projectLogSummary', null)->assertDontSee('Actividad de proyectos');
        $this->assertSame([], $queries);
        config(['project_log.enabled' => true]);
        Schema::partialMock()->shouldReceive('hasTable')->with('project_log_projects')->andReturn(false);
        $this->get(route('admin.gerencial'))->assertOk()->assertViewHas('projectLogSummary', null);
        $this->assertSame([], $queries);
    }

    public function test_project_summary_escapes_names_and_does_not_expand_dashboard_permissions(): void
    {
        $admin = User::factory()->administrador()->create();
        $project = ProjectLogProject::create(['name' => '<script>alert(1)</script>']);
        $this->entry($admin, '2026-10-29', 'creation', $project);
        $this->actingAs($admin)->get(route('admin.gerencial'))->assertOk()->assertSee($project->name)->assertDontSee($project->name, false);
        foreach ([User::factory()->soporte()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.gerencial'))->assertForbidden();
        }
    }
}
