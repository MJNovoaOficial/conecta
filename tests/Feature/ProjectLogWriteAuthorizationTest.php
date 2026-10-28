<?php

namespace Tests\Feature;

use App\Http\Controllers\ProjectLogController;
use App\Models\ProjectLogAttachment;
use App\Models\ProjectLogEntry;
use App\Models\ProjectLogProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class ProjectLogWriteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['project_log.enabled' => true]);
        Storage::fake('local');
    }

    private function payload(User $responsible, array $overrides = []): array
    {
        return array_replace([
            'new_project' => 'Proyecto protegido',
            'title' => 'Trabajo registrado',
            'description' => 'Conservar datos y respetar los permisos actuales.',
            'type' => 'correction',
            'performed_on' => '2026-10-28',
            'responsible_id' => $responsible->id,
            'submission_key' => (string) Str::uuid(),
        ], $overrides);
    }

    private function entry(User $author, User $responsible): ProjectLogEntry
    {
        return ProjectLogEntry::create([
            'project_id' => ProjectLogProject::create(['name' => 'Proyecto existente'])->id,
            'author_id' => $author->id, 'author_name' => $author->name,
            'responsible_id' => $responsible->id, 'responsible_name' => $responsible->name,
            'title' => 'Versión conservada', 'description' => 'Este texto no debe sobrescribirse.',
            'type' => 'improvement', 'performed_on' => '2026-10-28',
            'submission_key' => (string) Str::uuid(), 'version' => 1,
        ])->fresh();
    }

    private function staleActorRequest(User $actor, array $data): Request
    {
        $this->actingAs($actor);
        $request = Request::create('/bitacora', 'POST', $data, [], [
            'attachments' => [UploadedFile::fake()->image('no-debe-guardarse.png')],
        ]);
        $request->setUserResolver(fn () => $actor);
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->instance('request', $request);
        return $request;
    }

    public static function revokedWriters(): array
    {
        return [
            'publicar después de desactivación' => ['store', ['is_active' => false]],
            'publicar después de pérdida de rol' => ['store', ['role' => 'user']],
            'editar después de desactivación' => ['update', ['is_active' => false]],
            'editar después de pérdida de rol' => ['update', ['role' => 'user']],
        ];
    }

    #[DataProvider('revokedWriters')]
    public function test_write_rechecks_database_permissions_after_actor_was_hydrated(string $operation, array $revocation): void
    {
        $actor = User::factory()->soporte()->create();
        $responsible = User::factory()->soporte()->create();
        $entry = $operation === 'update' ? $this->entry($actor, $responsible) : null;
        $before = $entry?->getAttributes();
        $data = $this->payload($responsible, ['version' => 1]);
        $request = $this->staleActorRequest($actor, $data);

        // La petición conserva la identidad ya autorizada. La base refleja una
        // revocación posterior; no refrescar el objeto del resolver evita que
        // el test dependa de que otro middleware descubra el cambio primero.
        DB::table('usuarios')->where('id', $actor->id)->update($revocation);
        $this->assertTrue($request->user()->is_active);
        $this->assertSame('support', $request->user()->role);

        try {
            $controller = $this->app->make(ProjectLogController::class);
            if ($operation === 'store') $controller->store($request);
            else $controller->update($request, $entry->id);
            $this->fail('Una cuenta revocada no debe completar una escritura pendiente.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame($entry ? 1 : 0, ProjectLogEntry::count());
        $this->assertSame($entry ? 1 : 0, ProjectLogProject::count());
        if ($entry) $this->assertSame($before, $entry->fresh()->getAttributes());
        $this->assertSame(0, ProjectLogAttachment::count());
        $this->assertSame([], Storage::disk('local')->allFiles('project-log'));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'project_log_created']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'project_log_updated']);
    }

    public function test_demoted_admin_cannot_edit_another_author_using_hydrated_admin_identity(): void
    {
        $author = User::factory()->soporte()->create();
        $actor = User::factory()->administrador()->create();
        $entry = $this->entry($author, $author);
        $before = $entry->getAttributes();
        $request = $this->staleActorRequest($actor, $this->payload($author, ['version' => 1]));
        DB::table('usuarios')->where('id', $actor->id)->update(['role' => 'support']);
        $this->assertTrue($request->user()->isAdmin());

        try {
            $this->app->make(ProjectLogController::class)->update($request, $entry->id);
            $this->fail('El antiguo rol administrador no debe permitir editar publicaciones ajenas.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        $this->assertSame($before, $entry->fresh()->getAttributes());
        $this->assertSame(1, ProjectLogProject::count());
        $this->assertSame(0, ProjectLogAttachment::count());
        $this->assertSame([], Storage::disk('local')->allFiles('project-log'));
        $this->assertDatabaseMissing('audit_logs', ['action' => 'project_log_updated']);
    }

    public function test_create_ignores_injected_author_and_version_fields(): void
    {
        $actor = User::factory()->soporte()->create();
        $other = User::factory()->administrador()->create();
        $key = (string) Str::uuid();
        $this->actingAs($actor)->post(route('project-log.store'), $this->payload($actor, [
            'author_id' => $other->id, 'author_name' => 'Autor falso', 'version' => 999,
            'submission_key' => $key,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $entry = ProjectLogEntry::sole();
        $this->assertSame($actor->id, (int) $entry->author_id);
        $this->assertSame($actor->name, $entry->author_name);
        $this->assertSame(1, $entry->version);
        $this->assertSame($key, $entry->submission_key);
    }

    public function test_update_preserves_author_and_submission_key_and_controls_version_increment(): void
    {
        $actor = User::factory()->soporte()->create();
        $other = User::factory()->administrador()->create();
        $entry = $this->entry($actor, $actor);
        $key = $entry->submission_key;
        $this->actingAs($actor)->put(route('project-log.update', $entry), $this->payload($actor, [
            'project_id' => $entry->project_id, 'new_project' => null,
            'author_id' => $other->id, 'author_name' => 'Autor falso',
            'version' => 1, 'submission_key' => (string) Str::uuid(),
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $entry->refresh();
        $this->assertSame($actor->id, (int) $entry->author_id);
        $this->assertSame($actor->name, $entry->author_name);
        $this->assertSame($key, $entry->submission_key);
        $this->assertSame(2, $entry->version);
    }

    public function test_existing_responsible_sentinel_is_not_accepted_when_creating_a_record(): void
    {
        $actor = User::factory()->soporte()->create();
        $this->actingAs($actor)->post(route('project-log.store'), $this->payload($actor, ['responsible_id' => 'existing']))
            ->assertSessionHasErrors('responsible_id');
        $this->assertSame(0, ProjectLogEntry::count());
        $this->assertSame(0, ProjectLogProject::count());
    }
}
