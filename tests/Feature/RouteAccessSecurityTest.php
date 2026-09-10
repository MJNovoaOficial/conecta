<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\ArticuloImagen;
use App\Models\Department;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RouteAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_anonymous_visitors_are_redirected_from_sensitive_pages(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->get(route('profile.index'))->assertRedirect(route('login'));
    }

    public function test_inactive_accounts_cannot_log_in_or_keep_an_existing_session(): void
    {
        $department = $this->createDepartment('Cuentas');
        $inactiveAdmin = $this->createUser('admin', $department);
        $inactiveAdmin->update(['is_active' => false]);

        $this->post('/login', [
            'email' => $inactiveAdmin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($inactiveAdmin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('home'));
        $this->assertGuest();

        $activeUser = $this->createUser('user', $department);
        $this->post('/login', [
            'email' => $activeUser->email,
            'password' => 'password',
        ])->assertRedirect('/tickets');
        $this->assertAuthenticatedAs($activeUser);
    }

    public function test_non_admin_roles_cannot_open_any_administration_tab_by_url(): void
    {
        $department = $this->createDepartment('Seguridad');
        $regularUser = $this->createUser('user', $department);
        $supportUser = $this->createUser('support', $department);
        $adminUser = $this->createUser('admin', $department);

        $adminTabs = [
            'admin.dashboard',
            'admin.gerencial',
            'admin.users.index',
            'admin.departments.index',
            'admin.articulos.index',
            'admin.categories.index',
            'admin.sla.index',
            'admin.reports.index',
            'admin.reports.agents',
            'admin.priority-rules.index',
            'admin.audit.index',
            'admin.settings.index',
            'admin.states.index',
            'admin.manuales.index',
        ];

        foreach ([$regularUser, $supportUser] as $unauthorizedUser) {
            foreach ($adminTabs as $routeName) {
                $this->actingAs($unauthorizedUser)
                    ->get(route($routeName))
                    ->assertForbidden();
            }
        }

        $this->actingAs($adminUser)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_every_administration_route_keeps_both_access_middlewares(): void
    {
        $adminRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/'));

        $this->assertNotEmpty($adminRoutes);

        foreach ($adminRoutes as $route) {
            $this->assertContains('auth', $route->middleware(), $route->uri().' no exige autenticación.');
            $this->assertContains('admin', $route->middleware(), $route->uri().' no exige rol administrador.');
        }
    }

    public function test_sensitive_ticket_routes_keep_object_level_authorization(): void
    {
        $expectedMiddleware = [
            'tickets.show' => 'can:view,ticket',
            'tickets.panel' => 'can:view,ticket',
            'tickets.addComment' => 'can:comment,ticket',
            'tickets.updateStatus' => 'can:changeStatus,ticket',
            'tickets.updatePriority' => 'can:staff,ticket',
            'tickets.updateClassification' => 'can:staff,ticket',
            'tickets.assignTo' => 'can:staff,ticket',
            'tickets.selfAssign' => 'can:selfAssign,ticket',
            'tickets.forward' => 'can:staff,ticket',
            'tickets.close' => 'can:close,ticket',
            'tickets.reopen' => 'can:reopen,ticket',
        ];

        foreach ($expectedMiddleware as $routeName => $middleware) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, 'Falta la ruta '.$routeName.'.');
            $this->assertContains('auth', $route->middleware(), $routeName.' no exige autenticación.');
            $this->assertContains($middleware, $route->middleware(), $routeName.' no valida el objeto.');
        }
    }

    public function test_regular_users_cannot_open_support_statistics_by_url(): void
    {
        $department = $this->createDepartment('Mesa de ayuda');
        $regularUser = $this->createUser('user', $department);

        $this->actingAs($regularUser)
            ->get(route('tickets.my-stats'))
            ->assertForbidden();
    }

    public function test_a_user_cannot_view_another_users_ticket_or_attachment_by_guessing_ids(): void
    {
        $department = $this->createDepartment('Operaciones');
        $owner = $this->createUser('user', $department);
        $intruder = $this->createUser('user', $department);
        $ticket = $this->createTicket($owner, $department);
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_path' => 'tickets/'.$ticket->id.'/private.pdf',
            'file_name' => 'private.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 100,
            'uploaded_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('tickets.show', $ticket))
            ->assertOk();
        $this->actingAs($this->createUser('support', $department))
            ->get(route('tickets.show', $ticket))
            ->assertOk();
        $this->actingAs($intruder)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->get(route('tickets.panel', $ticket))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->get(route('files.attachment', $attachment))
            ->assertForbidden();
    }

    public function test_a_ticket_owner_cannot_invoke_support_actions_with_crafted_requests(): void
    {
        $department = $this->createDepartment('Infraestructura');
        $owner = $this->createUser('user', $department);
        $support = $this->createUser('support', $department);
        $ticket = $this->createTicket($owner, $department);

        $attempts = [
            ['put', 'tickets.updateStatus', ['status' => Ticket::STATUS_CLOSED]],
            ['put', 'tickets.updatePriority', ['priority' => 'critical']],
            ['put', 'tickets.updateClassification', []],
            ['post', 'tickets.assignTo', ['user_id' => $support->id]],
            ['post', 'tickets.selfAssign', []],
            ['post', 'tickets.forward', ['department_id' => $department->id]],
        ];

        foreach ($attempts as [$method, $routeName, $payload]) {
            $this->actingAs($owner)
                ->{$method}(route($routeName, $ticket), $payload)
                ->assertForbidden();
        }

        $ticket->refresh();
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->status);
        $this->assertNull($ticket->assigned_to);
        $this->assertSame('medium', $ticket->priority);
    }

    public function test_a_user_cannot_modify_another_users_ticket_by_changing_its_id(): void
    {
        $department = $this->createDepartment('Privacidad');
        $owner = $this->createUser('user', $department);
        $intruder = $this->createUser('user', $department);
        $ticket = $this->createTicket($owner, $department);

        $this->actingAs($intruder)
            ->post(route('tickets.addComment', $ticket), ['comment' => 'Acceso indebido'])
            ->assertForbidden();
        $this->actingAs($intruder)
            ->post(route('tickets.close', $ticket), ['solution_text' => 'Cierre no autorizado'])
            ->assertForbidden();
        $this->actingAs($intruder)
            ->post(route('tickets.reopen', $ticket), ['motivo' => 'Reapertura no autorizada'])
            ->assertForbidden();

        $this->assertSame(Ticket::STATUS_OPEN, $ticket->fresh()->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
    }

    public function test_notification_ids_do_not_expose_or_modify_another_users_notifications(): void
    {
        $department = $this->createDepartment('Notificaciones');
        $owner = $this->createUser('user', $department);
        $intruder = $this->createUser('user', $department);
        $notification = Notificacion::create([
            'user_id' => $owner->id,
            'type' => 'private',
            'title' => 'Mensaje privado del propietario',
            'body' => 'Contenido que no corresponde al intruso.',
        ]);

        $this->actingAs($intruder)
            ->get(route('notifications.read', $notification))
            ->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($intruder)
            ->getJson(route('notifications.recent'))
            ->assertOk()
            ->assertJsonMissing(['title' => $notification->title]);

        $this->actingAs($owner)
            ->get(route('notifications.read', $notification))
            ->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_internal_comment_attachments_are_not_exposed_to_the_ticket_owner(): void
    {
        Storage::fake('local');

        $department = $this->createDepartment('Información');
        $owner = $this->createUser('user', $department);
        $support = $this->createUser('support', $department);
        $ticket = $this->createTicket($owner, $department, $support);
        $internalComment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $support->id,
            'comment' => 'Diagnóstico interno reservado.',
            'ticket_status_at_comment' => Ticket::STATUS_OPEN,
            'is_internal' => true,
        ]);
        $publicAttachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'file_path' => 'tickets/'.$ticket->id.'/visible.txt',
            'file_name' => 'visible.txt',
            'file_type' => 'text/plain',
            'file_size' => 7,
            'uploaded_by' => $owner->id,
        ]);
        $internalAttachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'comment_id' => $internalComment->id,
            'file_path' => 'tickets/'.$ticket->id.'/comments/internal.txt',
            'file_name' => 'internal.txt',
            'file_type' => 'text/plain',
            'file_size' => 8,
            'uploaded_by' => $support->id,
        ]);
        Storage::disk('local')->put($publicAttachment->file_path, 'visible');
        Storage::disk('local')->put($internalAttachment->file_path, 'internal');

        $this->actingAs($owner)
            ->get(route('files.attachment', $publicAttachment))
            ->assertOk();
        $this->actingAs($owner)
            ->get(route('files.attachment', $internalAttachment))
            ->assertForbidden();
        $this->actingAs($support)
            ->get(route('files.attachment', $internalAttachment))
            ->assertOk();
    }

    public function test_images_from_inactive_knowledge_articles_are_only_visible_to_admins(): void
    {
        Storage::fake('local');

        $department = $this->createDepartment('Conocimiento');
        $regularUser = $this->createUser('user', $department);
        $admin = $this->createUser('admin', $department);
        $article = Articulo::create([
            'title' => 'Borrador privado',
            'content' => 'Contenido aún no publicado.',
            'is_active' => false,
            'created_by' => $admin->id,
        ]);
        $image = ArticuloImagen::create([
            'articulo_id' => $article->id,
            'ruta' => 'articulos/private.png',
            'nombre_original' => 'private.png',
            'orden' => 1,
        ]);
        Storage::disk('local')->put($image->ruta, 'image');

        $this->actingAs($regularUser)
            ->get(route('files.articulo-imagen', $image))
            ->assertNotFound();
        $this->actingAs($admin)
            ->get(route('files.articulo-imagen', $image))
            ->assertOk();
    }

    public function test_support_keeps_status_access_but_cannot_invoke_assigned_only_actions(): void
    {
        $department = $this->createDepartment('Soporte');
        $owner = $this->createUser('user', $department);
        $assignedSupport = $this->createUser('support', $department);
        $otherSupport = $this->createUser('support', $department);
        $ticket = $this->createTicket($owner, $department, $assignedSupport);

        $this->actingAs($otherSupport)
            ->put(route('tickets.updateStatus', $ticket), ['status' => Ticket::STATUS_IN_PROGRESS])
            ->assertRedirect();

        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);

        $this->actingAs($otherSupport)
            ->post(route('tickets.assignTo', $ticket), ['user_id' => $otherSupport->id])
            ->assertRedirect();
        $this->assertSame($assignedSupport->id, $ticket->fresh()->assigned_to);

        $this->actingAs($otherSupport)
            ->post(route('tickets.addComment', $ticket), [
                'comment' => 'Necesitamos información adicional para continuar.',
                'request_info' => 1,
            ])
            ->assertForbidden();

        $ticket->refresh();
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
    }

    public function test_support_can_take_a_free_ticket_and_then_manage_it(): void
    {
        $department = $this->createDepartment('Aplicaciones');
        $owner = $this->createUser('user', $department);
        $support = $this->createUser('support', $department);
        $ticket = $this->createTicket($owner, $department);

        $this->actingAs($support)
            ->post(route('tickets.selfAssign', $ticket))
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame($support->id, $ticket->assigned_to);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);

        $this->actingAs($support)
            ->put(route('tickets.updateStatus', $ticket), ['status' => Ticket::STATUS_RESOLVED])
            ->assertRedirect();

        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
    }

    public function test_guest_ticket_page_preserves_history_without_rendering_management_forms(): void
    {
        $department = $this->createDepartment('Servicio al cliente');
        $support = $this->createUser('support', $department);
        $ticket = Ticket::create([
            'ticket_number' => 'GUEST-0001',
            'department_id' => $department->id,
            'title' => 'Consulta de invitado',
            'description' => 'Descripción visible para el solicitante.',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'medium',
            'guest_name' => 'Invitado',
            'guest_email' => 'guest@example.test',
            'guest_token' => str_repeat('a', 40),
        ]);
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $support->id,
            'action' => 'assigned',
            'old_value' => null,
            'new_value' => (string) $support->id,
            'field_name' => 'assigned_to',
        ]);

        $this->get(route('tickets.guest.show', $ticket->guest_token))
            ->assertOk()
            ->assertSee('Historial')
            ->assertSee($support->name)
            ->assertDontSee(route('tickets.forward', $ticket), false)
            ->assertDontSee(route('tickets.assignTo', $ticket), false);
    }

    public function test_malformed_guest_tracking_links_are_rejected(): void
    {
        $this->get('/tickets/guest/123')->assertNotFound();
        $this->post('/tickets/guest/not-a-valid-token/comment', [
            'comment' => 'Intento con URL manipulada.',
        ])->assertNotFound();
    }

    public function test_avatar_route_rejects_paths_instead_of_plain_filenames(): void
    {
        $department = $this->createDepartment('Perfiles');
        $user = $this->createUser('user', $department);

        $this->actingAs($user)->get('/files/avatar/.env')->assertNotFound();
        $this->actingAs($user)->get('/files/avatar/folder/avatar.png')->assertNotFound();
    }

    private function createDepartment(string $name): Department
    {
        return Department::create([
            'name' => $name,
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function createUser(string $role, Department $department): User
    {
        return User::factory()->create([
            'role' => $role,
            'department_id' => $department->id,
            'is_active' => true,
        ]);
    }

    private function createTicket(
        User $owner,
        Department $department,
        ?User $assignedTo = null
    ): Ticket {
        return Ticket::create([
            'ticket_number' => 'TEST-'.fake()->unique()->numerify('######'),
            'user_id' => $owner->id,
            'department_id' => $department->id,
            'title' => 'Ticket de prueba',
            'description' => 'Información sensible del ticket.',
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'medium',
            'assigned_to' => $assignedTo?->id,
        ]);
    }
}
