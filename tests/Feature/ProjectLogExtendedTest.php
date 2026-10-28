<?php

namespace Tests\Feature;

use App\Models\ProjectLogEntry;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectLogExtendedTest extends TestCase
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

    private function payload(User $responsible, array $overrides = []): array
    {
        return array_replace([
            'new_project' => 'Proyecto de pruebas adicionales',
            'title' => 'Avance conservado',
            'description' => "Trabajo realizado.\nDetalles originales.",
            'type' => 'improvement',
            'performed_on' => '2026-09-30',
            'responsible_id' => $responsible->id,
            'submission_key' => (string) Str::uuid(),
        ], $overrides);
    }

    private function publish(User $author, ?User $responsible = null, array $overrides = []): ProjectLogEntry
    {
        $this->actingAs($author)->post(route('project-log.store'), $this->payload($responsible ?? $author, $overrides))
            ->assertSessionHasNoErrors()->assertRedirect();

        return ProjectLogEntry::latest('id')->firstOrFail();
    }

    private function editPayload(ProjectLogEntry $entry, array $overrides = []): array
    {
        return array_replace([
            'project_id' => $entry->project_id,
            'new_project' => null,
            'title' => 'Texto corregido',
            'description' => 'Se corrigió solamente la explicación del trabajo.',
            'type' => $entry->type,
            'performed_on' => $entry->performed_on->format('Y-m-d'),
            'responsible_id' => 'existing',
            'version' => $entry->version,
        ], $overrides);
    }

    public function test_second_attachment_failure_during_edit_preserves_previous_record_and_files(): void
    {
        $author = User::factory()->soporte()->create();
        $entry = $this->publish($author, overrides: ['attachments' => [UploadedFile::fake()->image('anterior.png')]]);
        $previous = $entry->getAttributes();
        $oldFile = $entry->attachments()->firstOrFail();
        $realDisk = Storage::disk('local');
        $previousContent = $realDisk->get($oldFile->path);
        $previousFiles = $realDisk->allFiles('project-log');
        $writes = 0;
        $newPath = null;

        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->twice()->andReturnUsing(function ($path, $file, $name, $options = []) use ($realDisk, &$writes, &$newPath) {
            if (++$writes === 2) return false;
            return $newPath = $realDisk->putFileAs($path, $file, $name, $options);
        });
        $disk->shouldReceive('delete')->once()->with(Mockery::on(fn ($path) => $path !== $oldFile->path))
            ->andReturnUsing(fn ($path) => $realDisk->delete($path));
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);

        $this->put(route('project-log.update', $entry), $this->editPayload($entry, [
            'responsible_id' => $author->id,
            'attachments' => [UploadedFile::fake()->image('nuevo-uno.png'), UploadedFile::fake()->image('nuevo-dos.png')],
        ]))->assertRedirect()->assertSessionHasErrors('attachments');

        $this->assertSame(2, $writes);
        $this->assertNotEmpty($newPath);
        $this->assertFalse($realDisk->exists($newPath));
        $this->assertSame($previous, $entry->fresh()->getAttributes());
        $this->assertSame($previousFiles, $realDisk->allFiles('project-log'));
        $this->assertSame($previousContent, $realDisk->get($oldFile->path));
        $this->assertSame([$oldFile->id], $entry->attachments()->pluck('id')->all());
        $this->assertDatabaseMissing('audit_logs', ['action' => 'project_log_updated', 'model_id' => $entry->id]);
    }

    public function test_empty_period_exports_a_valid_pdf_and_excel_with_headers_only(): void
    {
        $this->actingAs(User::factory()->soporte()->create());
        $range = ['date_from' => '2026-09-01', 'date_to' => '2026-09-30'];
        $pdf = $this->get(route('project-log.export', ['format' => 'pdf'] + $range))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $excel = $this->get(route('project-log.export', ['format' => 'excel'] + $range))->assertOk();
        $path = $excel->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(1, $sheet->getHighestDataRow());
            $this->assertSame('Fecha del trabajo', $sheet->getCell('A1')->getValue());
            $this->assertSame('Fecha de publicación', $sheet->getCell('H1')->getValue());
            $book->disconnectWorksheets();
        } finally { @unlink($path); }
    }

    public function test_exactly_500_entries_are_exported_completely_to_excel(): void
    {
        $author = User::factory()->soporte()->create();
        $entry = $this->publish($author);
        $row = $entry->getAttributes();
        unset($row['id']);
        $rows = [];
        for ($i = 1; $i < 500; $i++) {
            $rows[] = array_replace($row, ['submission_key' => (string) Str::uuid(), 'title' => 'Avance '.$i]);
        }
        DB::table('project_log_entries')->insert($rows);
        $response = $this->get(route('project-log.export', ['format' => 'excel', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30']))
            ->assertOk()->assertSessionHasNoErrors();
        $path = $response->baseResponse->getFile()->getPathname();
        try {
            $book = IOFactory::load($path);
            $sheet = $book->getActiveSheet();
            $this->assertSame(501, $sheet->getHighestDataRow());
            $this->assertSame('Avance 499', $sheet->getCell('C2')->getValue());
            $this->assertSame('Avance conservado', $sheet->getCell('C501')->getValue());
            $this->assertSame('30/09/2026', $sheet->getCell('A501')->getValue());
            $book->disconnectWorksheets();
        } finally { @unlink($path); }
    }

    public static function historicalResponsibleStates(): array
    {
        return ['inactive' => ['inactive'], 'role changed' => ['role'], 'deleted' => ['deleted']];
    }

    #[DataProvider('historicalResponsibleStates')]
    public function test_edit_can_preserve_the_historical_responsible_without_reassigning_work(string $state): void
    {
        $author = User::factory()->soporte()->create();
        $responsible = User::factory()->soporte()->create(['name' => 'Responsable histórico']);
        $entry = $this->publish($author, $responsible);
        $name = $entry->responsible_name;
        if ($state === 'inactive') $responsible->update(['is_active' => false]);
        elseif ($state === 'role') $responsible->update(['role' => 'user']);
        else $responsible->delete();
        $expectedId = $state === 'deleted' ? null : $responsible->id;

        $this->get(route('project-log.edit', $entry))->assertOk()->assertSee('value="existing"', false)->assertSee($name);
        $this->put(route('project-log.update', $entry), $this->editPayload($entry))->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $entry->fresh();
        $this->assertSame($expectedId, $fresh->responsible_id);
        $this->assertSame($name, $fresh->responsible_name);
        $this->assertSame('Texto corregido', $fresh->title);
        $this->assertSame(2, $fresh->version);
        $this->assertSame($author->id, $fresh->author_id);
    }

    public function test_existing_responsible_marker_is_not_allowed_when_creating_a_record(): void
    {
        $author = User::factory()->soporte()->create();
        $this->actingAs($author)->post(route('project-log.store'), $this->payload($author, ['responsible_id' => 'existing']))
            ->assertSessionHasErrors('responsible_id');
        $this->assertSame(0, ProjectLogEntry::count());
    }

    public function test_preserve_option_does_not_allow_selecting_a_new_inactive_or_regular_responsible(): void
    {
        $author = User::factory()->soporte()->create();
        $entry = $this->publish($author);
        $before = $entry->getAttributes();
        foreach ([User::factory()->soporte()->desactivado()->create(), User::factory()->create()] as $invalid) {
            $this->put(route('project-log.update', $entry), $this->editPayload($entry, ['responsible_id' => $invalid->id]))
                ->assertSessionHasErrors('responsible_id');
        }
        $this->assertSame($before, $entry->fresh()->getAttributes());
    }
}
