<?php

namespace Tests\Feature;

use App\Models\ProjectLogAttachment;
use App\Models\ProjectLogEntry;
use App\Models\ProjectLogProject;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ProjectLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['project_log.enabled' => true]);
        Storage::fake('local');
        Mail::fake();
        Notification::fake();
    }

    private function staff(): User { return User::factory()->soporte()->create(); }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'new_project' => 'Proyecto de ejemplo', 'title' => 'Mejora de búsqueda',
            'description' => "Se incorporó una búsqueda.\nConserva los registros existentes.",
            'type' => 'improvement', 'performed_on' => '2026-09-30',
            'responsible_id' => auth()->id(), 'submission_key' => (string) Str::uuid(),
        ], $overrides);
    }

    private function publish(User $user, array $extra = []): ProjectLogEntry
    {
        $this->actingAs($user)->post(route('project-log.store'), $this->payload($extra))->assertSessionHasNoErrors()->assertRedirect();
        return ProjectLogEntry::latest('id')->firstOrFail();
    }

    public function test_only_active_support_and_admin_have_access_to_every_endpoint(): void
    {
        $support = $this->staff();
        $entry = $this->publish($support, ['attachments' => [UploadedFile::fake()->image('captura.jpg')]]);
        $file = $entry->attachments()->firstOrFail();
        $getRoutes = [route('project-log.index'), route('project-log.create'), route('project-log.show', $entry),
            route('project-log.edit', $entry), route('project-log.export', 'pdf'), route('project-log.export', 'excel'),
            route('project-log.download', [$entry, $file])];
        foreach ($getRoutes as $url) $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->post(route('project-log.store'), [])->assertForbidden();
        $this->put(route('project-log.update', $entry), [])->assertForbidden();
        foreach ([$support, User::factory()->administrador()->create()] as $user) {
            $this->actingAs($user)->get(route('project-log.index'))->assertOk();
            $this->get(route('project-log.create'))->assertOk();
            $this->get(route('project-log.show', $entry))->assertOk();
        }
        $this->actingAs(User::factory()->soporte()->desactivado()->create())->get(route('project-log.index'))->assertRedirect();
        $this->get(route('project-log.index'))->assertRedirect(route('login'));
    }

    public function test_disabled_module_does_not_query_its_tables_or_show_navigation(): void
    {
        config(['project_log.enabled' => false]);
        $this->actingAs($this->staff());
        $queries = [];
        DB::listen(function ($query) use (&$queries) { if (str_contains($query->sql, 'project_log_')) $queries[] = $query->sql; });
        foreach ([route('project-log.index'), route('project-log.create'), route('project-log.show', 999), route('project-log.export', 'pdf')] as $url) $this->get($url)->assertNotFound();
        $this->post(route('project-log.store'), [])->assertNotFound();
        $this->put(route('project-log.update', 999), [])->assertNotFound();
        $this->get(route('tickets.index'))->assertOk()->assertDontSee('Bitácora de proyectos');
        $this->assertSame([], $queries);
    }

    public function test_missing_tables_fail_closed_before_reading_records(): void
    {
        Schema::partialMock()->shouldReceive('hasTable')->with('project_log_projects')->andReturn(false);
        $this->actingAs($this->staff())->get(route('project-log.show', 999))->assertNotFound();
    }

    public function test_multiple_updates_share_one_project_and_do_not_send_notifications(): void
    {
        $user = $this->staff();
        $first = $this->publish($user);
        $second = $this->publish($user, ['new_project' => null, 'project_id' => $first->project_id, 'title' => 'Segundo avance']);
        $this->assertSame($first->project_id, $second->project_id);
        $this->assertSame(1, ProjectLogProject::count());
        $this->assertSame(2, ProjectLogEntry::count());
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
    }

    public function test_repeated_form_creates_only_one_entry_and_one_attachment(): void
    {
        $this->actingAs($this->staff());
        $payload = $this->payload(['attachments' => [UploadedFile::fake()->image('foto.png')]]);
        $one = $this->post(route('project-log.store'), $payload)->assertRedirect();
        $two = $this->post(route('project-log.store'), $payload)->assertRedirect();
        $this->assertSame($one->headers->get('Location'), $two->headers->get('Location'));
        $this->assertSame(1, ProjectLogEntry::count());
        $this->assertSame(1, ProjectLogAttachment::count());
        $this->assertCount(1, Storage::disk('local')->allFiles('project-log'));
    }

    public function test_another_author_cannot_reuse_a_submission_key(): void
    {
        $entry = $this->publish($this->staff());
        $this->actingAs($this->staff())->post(route('project-log.store'), $this->payload(['submission_key' => $entry->submission_key]))->assertConflict();
        $this->assertSame(1, ProjectLogEntry::count());
    }

    public function test_author_and_admin_edit_but_other_support_cannot(): void
    {
        $author = $this->staff();
        $entry = $this->publish($author);
        $payload = $this->payload(['project_id' => $entry->project_id, 'new_project' => null, 'version' => 1, 'title' => 'Editado']);
        $other = $this->staff();
        $this->actingAs($other)->get(route('project-log.edit', $entry))->assertForbidden();
        $this->put(route('project-log.update', $entry), $payload)->assertForbidden();
        $this->actingAs($author)->put(route('project-log.update', $entry), $payload)->assertRedirect();
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin)->put(route('project-log.update', $entry), array_replace($payload, ['version' => 2, 'title' => 'Revisado']))->assertRedirect();
        $this->assertSame('Revisado', $entry->fresh()->title);
        $this->assertSame($author->id, $entry->fresh()->author_id);
        $this->assertSame(3, $entry->fresh()->version);
    }

    public function test_stale_edit_does_not_overwrite_a_newer_revision_or_store_files(): void
    {
        $user = $this->staff();
        $entry = $this->publish($user);
        $payload = $this->payload(['version' => 1, 'title' => 'Revisión válida']);
        $this->put(route('project-log.update', $entry), $payload)->assertSessionHasNoErrors();
        $this->put(route('project-log.update', $entry), array_replace($payload, ['title' => 'Obsoleta', 'attachments' => [UploadedFile::fake()->image('foto.png')]]))->assertSessionHasErrors('version');
        $this->assertSame('Revisión válida', $entry->fresh()->title);
        $this->assertSame(0, ProjectLogAttachment::count());
    }

    public function test_work_date_controls_list_and_both_report_queries_not_publication_date(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 28));
        $entry = $this->publish($this->staff());
        $this->assertSame('2026-09-30', $entry->performed_on->format('Y-m-d'));
        $this->get(route('project-log.index'))->assertOk()->assertDontSee('Mejora de búsqueda');
        $range = ['date_from' => '2026-09-30', 'date_to' => '2026-09-30'];
        $this->get(route('project-log.index', $range))->assertOk()->assertSee('Mejora de búsqueda');
        $this->get(route('project-log.show', $entry))->assertSee('30/09/2026');
        $pdf = $this->get(route('project-log.export', ['format' => 'pdf'] + $range))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $excel = $this->get(route('project-log.export', ['format' => 'excel'] + $range))->assertOk();
        $path = $excel->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $this->assertSame('30/09/2026', $book->getActiveSheet()->getCell('A2')->getValue());
            $this->assertSame(2, $book->getActiveSheet()->getHighestRow());
            $book->disconnectWorksheets();
        } finally { @unlink($path); }
    }

    public function test_excel_keeps_formula_like_content_as_text(): void
    {
        $this->publish($this->staff(), ['title' => '=HYPERLINK("https://example.invalid")', 'description' => '+1+1']);
        $response = $this->get(route('project-log.export', ['format' => 'excel', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30']))->assertOk();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $this->assertSame(DataType::TYPE_STRING, $book->getActiveSheet()->getCell('C2')->getDataType());
            $this->assertSame('+1+1', $book->getActiveSheet()->getCell('F2')->getValue());
            $book->disconnectWorksheets();
        } finally { @unlink($path); }
    }

    public function test_invalid_filters_are_rejected_for_list_pdf_and_excel(): void
    {
        $this->actingAs($this->staff());
        foreach ([route('project-log.index'), route('project-log.export', 'pdf'), route('project-log.export', 'excel')] as $url) {
            $this->get($url.'?date_from[]=invalid')->assertSessionHasErrors('date_from')->assertRedirect(route('project-log.index'));
            $this->get($url.'?date_from=2026-10-31&date_to=2026-10-01')->assertSessionHasErrors('date_to')->assertRedirect(route('project-log.index'));
        }
    }

    public function test_fields_and_responsible_are_validated_without_creating_partial_projects(): void
    {
        $this->actingAs($this->staff());
        foreach ([['type' => 'unknown'], ['performed_on' => '2026-02-30'], ['responsible_id' => User::factory()->create()->id], ['title' => str_repeat('x', 181)]] as $bad) {
            $this->post(route('project-log.store'), $this->payload($bad))->assertSessionHasErrors();
        }
        $this->assertSame(0, ProjectLogProject::count());
        $this->assertSame(0, ProjectLogEntry::count());
    }

    public function test_project_selection_is_exclusive(): void
    {
        $entry = $this->publish($this->staff());
        $this->post(route('project-log.store'), $this->payload(['project_id' => $entry->project_id]))->assertSessionHasErrors('project_id');
        $this->post(route('project-log.store'), $this->payload(['new_project' => null]))->assertSessionHasErrors();
        $this->assertSame(1, ProjectLogEntry::count());
    }

    public function test_private_attachment_download_requires_staff_and_correct_entry(): void
    {
        $staff = $this->staff();
        $entry = $this->publish($staff, ['attachments' => [UploadedFile::fake()->image('captura.png')]]);
        $attachment = $entry->attachments()->firstOrFail();
        $url = route('project-log.download', [$entry, $attachment]);
        $response = $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('project-log.download', [999, $attachment]))->assertNotFound();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect(route('login'));
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_attachment_content_extension_size_and_total_count_are_checked(): void
    {
        $staff = $this->staff();
        $malicious = UploadedFile::fake()->createWithContent('ataque.jpg', '<?php echo "bad";');
        foreach ([[new UploadedFile($malicious->getPathname(), 'ataque.jpg', null, null, true)],
            [UploadedFile::fake()->create('grande.pdf', 5121, 'application/pdf')],
            array_map(fn () => UploadedFile::fake()->image('foto.png'), range(1, 4))] as $files) {
            $this->actingAs($staff)->post(route('project-log.store'), $this->payload(['attachments' => $files]))->assertSessionHasErrors();
        }
        $entry = $this->publish($staff, ['attachments' => array_map(fn () => UploadedFile::fake()->image('foto.png'), range(1, 3))]);
        $this->put(route('project-log.update', $entry), $this->payload(['version' => 1, 'attachments' => [UploadedFile::fake()->image('extra.png')]]))->assertSessionHasErrors('attachments');
        $this->assertSame(3, ProjectLogAttachment::count());
    }

    public function test_failed_disk_write_rolls_back_project_entry_and_audit(): void
    {
        $this->actingAs($this->staff());
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->post(route('project-log.store'), $this->payload(['attachments' => [UploadedFile::fake()->image('foto.png')]]))->assertSessionHasErrors('attachments');
        $this->assertSame(0, ProjectLogEntry::count());
        $this->assertSame(0, ProjectLogProject::count());
        $this->assertDatabaseMissing('audit_logs', ['action' => 'project_log_created']);
    }

    public function test_failed_attachment_insert_cleans_file_and_rolls_back_entry(): void
    {
        $this->actingAs($this->staff());
        $event = 'eloquent.creating: '.ProjectLogAttachment::class;
        Event::listen($event, fn () => throw new \RuntimeException('Simulated insert failure'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('project-log.store'), $this->payload(['attachments' => [UploadedFile::fake()->image('foto.png')]]));
            $this->fail('Expected storage transaction to fail');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated insert failure', $e->getMessage());
        } finally { Event::forget($event); }
        $this->assertSame(0, ProjectLogEntry::count());
        $this->assertSame(0, ProjectLogProject::count());
        $this->assertSame([], Storage::disk('local')->allFiles('project-log'));
    }

    public function test_html_is_escaped_and_edits_keep_existing_attachments(): void
    {
        $entry = $this->publish($this->staff(), ['title' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(1)>',
            'attachments' => [UploadedFile::fake()->image('foto.png')]]);
        $this->get(route('project-log.show', $entry))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<img src=x', false);
        $file = $entry->attachments()->firstOrFail();
        $this->put(route('project-log.update', $entry), $this->payload(['version' => 1, 'attachments' => []]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($file->path);
        $this->assertSame(1, $entry->attachments()->count());
    }

    public function test_module_operations_leave_existing_users_and_tickets_unchanged(): void
    {
        $staff = $this->staff();
        $requester = User::factory()->create();
        $ticket = Ticket::create(['ticket_number' => 'LOG-PRESERVE', 'user_id' => $requester->id, 'title' => 'Conservar', 'priority' => 'medium', 'status' => 'open'])->fresh();
        $before = [$ticket->getAttributes(), $staff->fresh()->getAttributes(), $requester->fresh()->getAttributes()];
        $entry = $this->publish($staff);
        $this->put(route('project-log.update', $entry), $this->payload(['version' => 1]))->assertSessionHasNoErrors();
        $this->assertSame($before, [$ticket->fresh()->getAttributes(), $staff->fresh()->getAttributes(), $requester->fresh()->getAttributes()]);
    }

    public function test_migration_refuses_rollback_when_records_exist(): void
    {
        $this->publish($this->staff());
        $migration = require database_path('migrations/2026_10_28_120000_create_project_log_tables.php');
        try { $migration->down(); $this->fail('Rollback must refuse existing data'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('contiene registros', $e->getMessage()); }
        $this->assertSame(1, ProjectLogEntry::count());
        $this->assertTrue(Schema::hasTable('project_log_attachments'));
    }

    public function test_reports_refuse_to_silently_truncate_more_than_500_entries(): void
    {
        $entry = $this->publish($this->staff());
        $row = $entry->getAttributes();
        unset($row['id']);
        $rows = [];
        for ($i = 0; $i < 500; $i++) $rows[] = array_replace($row, ['submission_key' => (string) Str::uuid()]);
        DB::table('project_log_entries')->insert($rows);
        foreach (['pdf', 'excel'] as $format) {
            $this->get(route('project-log.export', ['format' => $format, 'date_from' => '2026-09-01', 'date_to' => '2026-09-30']))
                ->assertRedirect(route('project-log.index'))->assertSessionHasErrors('date_from');
        }
        $this->assertSame(501, ProjectLogEntry::count());
    }

    public function test_new_navigation_is_available_to_staff_but_hidden_from_regular_users(): void
    {
        $this->actingAs($this->staff())->get(route('tickets.index'))->assertOk()->assertSee('Bitácora de proyectos');
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.dashboard'))->assertOk()->assertSee('Bitácora de proyectos');
        $this->actingAs(User::factory()->create())->get(route('tickets.index'))->assertOk()->assertDontSee('Bitácora');
    }

    public function test_migration_preflight_never_overwrites_existing_module_tables(): void
    {
        $entry = $this->publish($this->staff());
        $migration = require database_path('migrations/2026_10_28_120000_create_project_log_tables.php');
        try { $migration->up(); $this->fail('Existing tables must never be overwritten'); }
        catch (\RuntimeException $e) { $this->assertStringContainsString('Ya existe', $e->getMessage()); }
        $this->assertSame($entry->getAttributes(), $entry->fresh()->getAttributes());
    }
}
