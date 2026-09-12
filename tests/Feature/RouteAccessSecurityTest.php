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

    public function test_visitantes_sin_sesion_son_redirigidos_desde_paginas_sensibles(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->get(route('profile.index'))->assertRedirect(route('login'));
    }

    public function test_cuentas_inactivas_no_pueden_iniciar_ni_conservar_sesion(): void
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

    public function test_roles_no_administradores_no_abren_pestanas_administrativas_por_url(): void
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

    public function test_todas_las_rutas_administrativas_conservan_ambos_middleware_de_acceso(): void
    {
        $adminRoutes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/'));

        $this->assertNotEmpty($adminRoutes);

        foreach ($adminRoutes as $route) {
            $this->assertContains('auth', $route->middleware(), $route->uri().' no exige autenticación.');
            $this->assertContains('admin', $route->middleware(), $route->uri().' no exige rol administrador.');
        }
    }

    public function test_rutas_sensibles_de_tickets_conservan_autorizacion_por_objeto(): void
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

    public function test_usuario_comun_no_abre_estadisticas_de_soporte_por_url(): void
    {
        $department = $this->createDepartment('Mesa de ayuda');
        $regularUser = $this->createUser('user', $department);

        $this->actingAs($regularUser)
            ->get(route('tickets.my-stats'))
            ->assertForbidden();
    }

    public function test_usuario_no_ve_ticket_ni_adjunto_ajeno_adivinando_ids(): void
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

    public function test_propietario_no_invoca_acciones_de_soporte_con_peticiones_manipuladas(): void
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

    public function test_asignacion_rechaza_como_destino_a_un_usuario_comun(): void
    {
        $department = $this->createDepartment('Asignaciones');
        $owner = $this->createUser('user', $department);
        $admin = $this->createUser('admin', $department);
        $regularUser = $this->createUser('user', $department);
        $ticket = $this->createTicket($owner, $department);

        $this->actingAs($admin)
            ->post(route('tickets.assignTo', $ticket), ['user_id' => $regularUser->id])
            ->assertSessionHasErrors('user_id');

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_usuario_no_modifica_ticket_ajeno_cambiando_el_id(): void
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

    public function test_ids_de_notificacion_no_exponen_ni_modifican_notificaciones_ajenas(): void
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

    public function test_adjuntos_de_comentarios_internos_no_se_exponen_al_propietario(): void
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

    public function test_imagenes_de_articulos_inactivos_solo_son_visibles_para_administradores(): void
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

    public function test_soporte_conserva_cambio_de_estado_pero_no_acciones_del_agente_asignado(): void
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

    public function test_soporte_puede_tomar_un_ticket_libre_y_luego_gestionarlo(): void
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

    public function test_pagina_de_invitado_conserva_historial_sin_formularios_de_gestion(): void
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

    public function test_enlaces_de_seguimiento_de_invitado_malformados_son_rechazados(): void
    {
        $this->get('/tickets/guest/123')->assertNotFound();
        $this->post('/tickets/guest/not-a-valid-token/comment', [
            'comment' => 'Intento con URL manipulada.',
        ])->assertNotFound();
    }

    public function test_ruta_de_avatar_rechaza_rutas_en_lugar_de_nombres_de_archivo(): void
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
