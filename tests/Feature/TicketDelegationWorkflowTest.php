<?php

namespace Tests\Feature;

use App\Jobs\CloseResolvedTicketsJob;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketDelegation;
use App\Models\TicketHistory;
use App\Models\User;
use App\Support\TicketDelegationAccess;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketDelegationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ticket_delegation.enabled' => true]);
        Mail::fake();
        Notification::fake();
        Storage::fake('local');
        $this->travelTo(now()->startOfSecond());
    }

    private function context(): array
    {
        $ti = Department::create(['name' => 'Tecnologia', 'is_active' => true, 'default_role' => 'user']);
        $sales = Department::create(['name' => 'Ventas', 'is_active' => true, 'default_role' => 'user']);
        $logistics = Department::create(['name' => 'Logistica', 'is_active' => true, 'default_role' => 'user']);
        return [
            'ti' => $ti, 'sales' => $sales, 'logistics' => $logistics,
            'agent' => User::factory()->soporte()->create(['department_id' => $ti->id]),
            'admin' => User::factory()->administrador()->create(['department_id' => $ti->id]),
            'otherAgent' => User::factory()->soporte()->create(['department_id' => $ti->id]),
            'owner' => User::factory()->create(['department_id' => $logistics->id]),
            'delegate' => User::factory()->create(['department_id' => $sales->id]),
            'colleague' => User::factory()->create(['department_id' => $sales->id]),
        ];
    }

    private function ticket(array $context, array $extra = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'DELEG-'.fake()->unique()->numerify('##########'),
            'user_id' => $context['owner']->id, 'assigned_to' => $context['agent']->id,
            'department_id' => $context['logistics']->id, 'title' => 'Caso de colaboración',
            'description' => 'Descripción original que debe conservarse.',
            'priority' => 'medium', 'status' => Ticket::STATUS_IN_PROGRESS,
            'sla_resolution_deadline_at' => now()->addDays(2), 'reopened_count' => 0,
        ], $extra))->fresh();
    }

    private function grant(array $context, Ticket $ticket, ?User $delegate = null, ?User $manager = null): TicketDelegation
    {
        $latest = TicketDelegation::where('ticket_id', $ticket->id)->latest('id')->first();
        $this->actingAs($manager ?? $context['agent'])->post(route('tickets.delegations.store', $ticket), [
            'delegate_id' => ($delegate ?? $context['delegate'])->id,
            'instructions' => 'Revisar los datos de este caso y proponer una solución concreta.',
            'expected_id' => $latest?->id ?? 0, 'version' => $latest?->version ?? 0,
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        return TicketDelegation::where('ticket_id', $ticket->id)->latest('id')->firstOrFail();
    }

    private function propose(array $context, Ticket $ticket, TicketDelegation $delegation, string $text = 'Se corrigieron los datos y se verificó el funcionamiento.'): void
    {
        $this->actingAs($context['delegate'])->post(route('tickets.delegations.propose', [$ticket, $delegation]), [
            'version' => $delegation->version, 'solution_text' => $text,
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $delegation->refresh();
    }

    private function delegationComment(TicketDelegation $delegation, array $extra = []): array
    {
        return array_merge(['comment' => 'Revisé el caso; por favor confirma el resultado.',
            'delegation_id' => $delegation->id, 'delegation_version' => $delegation->version], $extra);
    }

    private function usersSnapshot(): array
    {
        return DB::table('usuarios')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public function test_encargar_conserva_responsable_departamento_sla_usuarios_y_casos_existentes(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context, ['status' => Ticket::STATUS_PENDING_USER, 'response_deadline_at' => now()->addHours(2)]);
        $other = $this->ticket($context);
        $otherBefore = $other->getAttributes();
        $users = $this->usersSnapshot();
        $stable = array_intersect_key($ticket->getAttributes(), array_flip(['user_id', 'assigned_to', 'department_id', 'description', 'priority', 'sla_resolution_deadline_at', 'created_at']));
        $delegation = $this->grant($context, $ticket);
        $ticket->refresh();
        $this->assertSame($stable, array_intersect_key($ticket->getAttributes(), $stable));
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->status);
        $this->assertNull($ticket->response_deadline_at);
        $this->assertNull($ticket->resolved_at);
        $this->assertSame('active', $delegation->status);
        $this->assertSame(1, $delegation->version);
        $this->assertSame($context['delegate']->id, $delegation->delegate_id);
        $this->assertSame($context['agent']->id, $delegation->responsible_id);
        $this->assertSame($users, $this->usersSnapshot());
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertDatabaseCount('tickets', 2);
    }

    public function test_solo_ti_responsable_o_admin_pueden_otorgar_el_encargo(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $before = $ticket->getAttributes();
        foreach (['owner', 'delegate', 'colleague', 'otherAgent'] as $key) {
            $this->actingAs($context[$key])->post(route('tickets.delegations.store', $ticket), [
                'delegate_id' => $context['delegate']->id, 'instructions' => 'No debe concederse este encargo.', 'expected_id' => 0, 'version' => 0,
            ])->assertForbidden();
            $this->assertDatabaseCount('ticket_delegations', 0);
            $this->assertSame($before, $ticket->fresh()->getAttributes());
        }
        $delegation = $this->grant($context, $ticket, manager: $context['admin']);
        $this->assertSame($context['admin']->id, $delegation->delegated_by);
        $this->assertSame($context['agent']->id, $ticket->fresh()->assigned_to);
    }

    public function test_no_se_encarga_a_rol_general_solicitante_inactivo_o_mismo_departamento_ti(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $before = $ticket->getAttributes();
        $inactiveDepartment = Department::create(['name' => 'Area inactiva', 'is_active' => false]);
        $invalid = [
            $context['owner'], $context['agent'], $context['admin'],
            User::factory()->create(['department_id' => $context['ti']->id]),
            User::factory()->create(['department_id' => null]),
            User::factory()->create(['department_id' => $context['sales']->id, 'is_active' => false]),
            User::factory()->create(['department_id' => $inactiveDepartment->id]),
            User::factory()->soporte()->create(['department_id' => $context['sales']->id]),
        ];
        foreach ($invalid as $user) {
            $this->actingAs($context['agent'])->post(route('tickets.delegations.store', $ticket), [
                'delegate_id' => $user->id, 'instructions' => 'Este destinatario no debe quedar encargado.', 'expected_id' => 0, 'version' => 0,
            ])->assertSessionHasErrors('delegate_id');
            $this->assertDatabaseCount('ticket_delegations', 0);
            $this->assertSame($before, $ticket->fresh()->getAttributes());
        }
    }

    public function test_no_se_otorgan_encargos_a_tickets_terminales_o_sin_responsable_valido(): void
    {
        $context = $this->context();
        foreach ([['status' => Ticket::STATUS_RESOLVED], ['status' => Ticket::STATUS_CLOSED], ['assigned_to' => null]] as $extra) {
            $ticket = $this->ticket($context, $extra);
            $before = $ticket->getAttributes();
            $this->actingAs($context['admin'])->post(route('tickets.delegations.store', $ticket), [
                'delegate_id' => $context['delegate']->id, 'instructions' => 'No habilitar casos que ya finalizaron.', 'expected_id' => 0, 'version' => 0,
            ])->assertForbidden();
            $this->assertSame($before, $ticket->fresh()->getAttributes());
        }
        $this->assertDatabaseCount('ticket_delegations', 0);
    }

    public function test_el_permiso_es_individual_y_solo_abre_el_caso_encargado_en_detalle_y_panel(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $other = $this->ticket($context);
        $this->grant($context, $ticket);
        foreach (['tickets.show', 'tickets.panel'] as $route) {
            $this->actingAs($context['delegate'])->get(route($route, $ticket))->assertOk()->assertSee($ticket->ticket_number);
            $this->get(route($route, $other))->assertForbidden();
            $this->actingAs($context['colleague'])->get(route($route, $ticket))->assertForbidden();
        }
        $this->assertSame('user', $context['delegate']->fresh()->role);
        $this->assertSame($context['sales']->id, $context['delegate']->fresh()->department_id);
    }

    public function test_listado_agrupa_propios_y_encargos_sin_saltar_filtros_ni_mostrar_ajenos(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context, ['title' => 'ENCARGO CLAVE visible']);
        $own = $this->ticket($context, ['user_id' => $context['delegate']->id, 'title' => 'Propio sin coincidencia']);
        $hidden = $this->ticket($context, ['title' => 'ENCARGO CLAVE ajeno']);
        $this->grant($context, $ticket);
        $this->actingAs($context['delegate'])->get(route('tickets.index'))->assertOk()
            ->assertSee($ticket->ticket_number)->assertSee($own->ticket_number)->assertDontSee($hidden->ticket_number);
        $this->get(route('tickets.index', ['search' => 'ENCARGO CLAVE']))->assertOk()
            ->assertSee($ticket->ticket_number)->assertDontSee($own->ticket_number)->assertDontSee($hidden->ticket_number);
        $this->get(route('tickets.index', ['status' => 'closed']))->assertOk()
            ->assertDontSee($ticket->ticket_number)->assertDontSee($own->ticket_number)->assertDontSee($hidden->ticket_number);
    }

    public function test_encargado_responde_y_adjunta_solo_en_su_caso_sin_plazo_de_cierre_por_inactividad(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $this->actingAs($context['delegate'])->post(route('tickets.addComment', $ticket), $this->delegationComment($delegation, [
            'attachments' => [UploadedFile::fake()->createWithContent('resultado.txt', 'Resultado de la revisión')],
        ]))->assertSessionHasNoErrors()->assertSessionHas('success');
        $comment = $ticket->comments()->sole();
        $this->assertSame($context['delegate']->id, $comment->user_id);
        $this->assertFalse((bool) $comment->is_internal);
        $this->assertSame(Ticket::STATUS_PENDING_USER, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->response_deadline_at);
        $attachment = $comment->attachments()->sole();
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->get(route('files.attachment', $attachment))->assertOk();
        $this->actingAs($context['owner'])->get(route('files.attachment', $attachment))->assertOk();
        $this->actingAs($context['colleague'])->get(route('files.attachment', $attachment))->assertForbidden();
    }

    public function test_no_puede_crear_notas_internas_ni_forzar_solicitar_informacion(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs($context['delegate'])->post(route('tickets.addComment', $ticket), $this->delegationComment($delegation, ['is_internal' => 1]))
            ->assertSessionHasErrors('comment');
        $this->post(route('tickets.addComment', $ticket), $this->delegationComment($delegation, ['request_info' => 1]))->assertForbidden();
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame(0, $ticket->comments()->count());
        $this->assertDatabaseCount('adjuntos_ticket', 0);
    }

    public function test_notas_internas_y_sus_adjuntos_no_se_exponen_por_vista_o_id_directo(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $this->grant($context, $ticket);
        $comment = TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $context['agent']->id,
            'comment' => 'NOTA INTERNA PRIVADA DE TI', 'is_internal' => true]);
        Storage::disk('local')->put('tickets/privado.txt', 'Contenido interno');
        $attachment = TicketAttachment::create(['ticket_id' => $ticket->id, 'comment_id' => $comment->id,
            'file_path' => 'tickets/privado.txt', 'file_name' => 'solo-ti.txt', 'file_type' => 'text/plain', 'file_size' => 17,
            'uploaded_by' => $context['agent']->id]);
        foreach (['tickets.show', 'tickets.panel'] as $route) {
            $this->actingAs($context['delegate'])->get(route($route, $ticket))->assertOk()
                ->assertDontSee('NOTA INTERNA PRIVADA DE TI')->assertDontSee('solo-ti.txt');
        }
        $this->get(route('files.attachment', $attachment))->assertForbidden();
        $this->actingAs($context['agent'])->get(route('files.attachment', $attachment))->assertOk();
    }

    public function test_encargado_no_puede_resolver_cerrar_reasignar_derivar_o_acceder_a_administracion(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $before = $ticket->fresh()->getAttributes();
        $rowBefore = $delegation->getAttributes();
        $this->actingAs($context['delegate']);
        foreach ([
            ['tickets.resolve', ['solution_text' => 'Intento de marcar Resuelto sin revisión.']],
            ['tickets.close', ['solution_text' => 'Intento de cierre no permitido.']],
            ['tickets.reopen', ['motivo' => 'Intento de reapertura no permitido.']],
            ['tickets.selfAssign', []], ['tickets.assignTo', ['user_id' => $context['delegate']->id]],
            ['tickets.forward', ['department_id' => $context['sales']->id]],
        ] as [$route, $data]) {
            $this->post(route($route, $ticket), $data)->assertForbidden();
        }
        $this->put(route('tickets.updatePriority', $ticket), ['priority' => 'critical'])->assertForbidden();
        $this->put(route('tickets.updateClassification', $ticket), [])->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->post(route('tickets.delegations.approve', [$ticket, $delegation]), ['version' => 1])->assertForbidden();
        $this->post(route('tickets.delegations.revoke', [$ticket, $delegation]), ['version' => 1])->assertForbidden();
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame($rowBefore, $delegation->fresh()->getAttributes());
    }

    public function test_propuesta_ajustes_reenvio_aprobacion_y_cierre_respetan_el_flujo_existente(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $this->propose($context, $ticket, $delegation);
        $this->assertSame('submitted', $delegation->status);
        $this->assertSame(2, $delegation->version);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertNull($ticket->fresh()->solution_text);
        $this->assertNull($ticket->fresh()->resolved_at);
        $this->actingAs($context['agent'])->post(route('tickets.delegations.changes', [$ticket, $delegation]), [
            'version' => 2, 'feedback' => 'Verificar también el segundo registro antes de aprobar.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $delegation->refresh();
        $this->assertSame('active', $delegation->status);
        $this->assertSame(3, $delegation->version);
        $this->assertSame('Verificar también el segundo registro antes de aprobar.', $delegation->feedback);
        $solution = 'Se corrigieron ambos registros y se verificó el resultado final.';
        $this->propose($context, $ticket, $delegation, $solution);
        $this->assertSame(4, $delegation->version);
        $this->actingAs($context['agent'])->post(route('tickets.delegations.approve', [$ticket, $delegation]), ['version' => 4])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $delegation->refresh();
        $ticket->refresh();
        $this->assertSame('approved', $delegation->status);
        $this->assertSame(5, $delegation->version);
        $this->assertSame($context['agent']->id, $delegation->reviewed_by);
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->status);
        $this->assertSame($solution, $ticket->solution_text);
        $this->assertTrue($ticket->resolved_at->equalTo(now()));
        $this->assertNull($ticket->closed_at);
        $this->assertSame(1, $ticket->history()->where('action', 'solution_registered')->count());
        $this->travel(59)->minutes();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->travel(2)->minutes();
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->history()->where('action', 'auto_closed_resolved')->count());
        $this->assertSame($context['agent']->id, $ticket->fresh()->assigned_to);
    }

    public function test_propuesta_privada_no_llega_al_solicitante_y_ti_no_puede_omitir_su_revision(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $proposal = 'PROPUESTA PRIVADA con verificación pendiente de revisión TI.';
        $this->propose($context, $ticket, $delegation, $proposal);
        foreach (['tickets.show', 'tickets.panel'] as $route) {
            $this->actingAs($context['delegate'])->get(route($route, $ticket))->assertOk()->assertSee($proposal);
            $this->actingAs($context['agent'])->get(route($route, $ticket))->assertOk()->assertSee($proposal);
            $this->actingAs($context['owner'])->get(route($route, $ticket))->assertOk()
                ->assertDontSee($proposal)->assertDontSee($delegation->instructions);
            $this->actingAs($context['otherAgent'])->get(route($route, $ticket))->assertOk()->assertDontSee($proposal);
        }
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs($context['agent'])->post(route('tickets.resolve', $ticket), ['solution_text' => 'No debe saltar la revisión del encargo.'])
            ->assertSessionHasErrors('delegation');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame(0, $ticket->comments()->where('comment', $proposal)->count());
        $this->assertSame(0, $ticket->history()->where('action', 'solution_registered')->count());
    }

    public function test_administrador_supervisa_y_aprueba_sin_convertir_al_encargado_en_soporte(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket, manager: $context['admin']);
        $this->propose($context, $ticket, $delegation);
        $users = $this->usersSnapshot();
        $this->actingAs($context['admin'])->post(route('tickets.delegations.approve', [$ticket, $delegation]), ['version' => 2])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($context['admin']->id, $delegation->fresh()->reviewed_by);
        $this->assertSame($context['agent']->id, $ticket->fresh()->assigned_to);
        $this->assertSame($users, $this->usersSnapshot());
    }

    public function test_version_antigua_no_sobrescribe_propuesta_ni_estado(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $before = $ticket->fresh()->getAttributes();
        $rowBefore = $delegation->getAttributes();
        $this->actingAs($context['delegate'])->post(route('tickets.delegations.propose', [$ticket, $delegation]), [
            'version' => 2, 'solution_text' => 'Versión distinta que no debe guardarse.',
        ])->assertSessionHasErrors('delegation');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame($rowBefore, $delegation->fresh()->getAttributes());
    }

    public function test_doble_aprobacion_no_reinicia_hora_ni_duplica_historial(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $this->propose($context, $ticket, $delegation);
        $url = route('tickets.delegations.approve', [$ticket, $delegation]);
        $this->actingAs($context['agent'])->post($url, ['version' => 2])->assertSessionHas('success');
        $before = $ticket->fresh()->getAttributes();
        $rowBefore = $delegation->fresh()->getAttributes();
        $this->travel(10)->minutes();
        $this->post($url, ['version' => 2])->assertRedirect()->assertSessionHas('error');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame($rowBefore, $delegation->fresh()->getAttributes());
        $this->assertSame(1, $ticket->history()->where('action', 'solution_registered')->count());
    }

    public function test_retirar_encargo_conserva_propuesta_mensajes_adjuntos_y_usuarios(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $this->actingAs($context['delegate'])->post(route('tickets.addComment', $ticket), $this->delegationComment($delegation, [
            'attachments' => [UploadedFile::fake()->createWithContent('avance.txt', 'Avance que debe conservarse')],
        ]))->assertSessionHas('success');
        $this->propose($context, $ticket, $delegation);
        $proposal = $delegation->proposal_text;
        $users = $this->usersSnapshot();
        $comment = $ticket->comments()->sole();
        $attachment = $comment->attachments()->sole();
        $commentBefore = $comment->getAttributes();
        $attachmentBefore = $attachment->getAttributes();
        $this->actingAs($context['agent'])->post(route('tickets.delegations.revoke', [$ticket, $delegation]), ['version' => 2])->assertSessionHas('success');
        $this->assertSame('revoked', $delegation->fresh()->status);
        $this->assertSame(3, $delegation->fresh()->version);
        $this->assertSame($proposal, $delegation->fresh()->proposal_text);
        $this->assertSame($commentBefore, $comment->fresh()->getAttributes());
        $this->assertSame($attachmentBefore, $attachment->fresh()->getAttributes());
        $this->assertSame($users, $this->usersSnapshot());
        Storage::disk('local')->assertExists($attachment->file_path);
        $this->actingAs($context['delegate'])->get(route('tickets.show', $ticket))->assertForbidden();
        $this->get(route('files.attachment', $attachment))->assertForbidden();
        $this->post(route('tickets.addComment', $ticket), $this->delegationComment($delegation))->assertForbidden();
        $this->assertSame(1, $ticket->comments()->count());
    }

    public function test_cambiar_encargado_agrega_fila_y_anula_permiso_anterior_sin_tocar_asignacion_ti(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $old = $this->grant($context, $ticket);
        $this->propose($context, $ticket, $old);
        $proposal = $old->proposal_text;
        $new = $this->grant($context, $ticket, $context['colleague']);
        $this->assertGreaterThan($old->id, $new->id);
        $this->assertSame('revoked', $old->fresh()->status);
        $this->assertSame($proposal, $old->fresh()->proposal_text);
        $this->assertSame('active', $new->status);
        $this->assertSame(1, $new->version);
        $this->assertDatabaseCount('ticket_delegations', 2);
        $this->assertSame($context['agent']->id, $ticket->fresh()->assigned_to);
        $this->actingAs($context['delegate'])->get(route('tickets.show', $ticket))->assertForbidden();
        $this->actingAs($context['colleague'])->get(route('tickets.show', $ticket))->assertOk();
        $this->actingAs($context['agent'])->post(route('tickets.delegations.store', $ticket), [
            'delegate_id' => $context['delegate']->id, 'instructions' => 'Intento desde formulario viejo que no debe reemplazar el encargo.',
            'expected_id' => $old->id, 'version' => 2,
        ])->assertSessionHasErrors('delegation');
        $this->assertDatabaseCount('ticket_delegations', 2);
        $this->assertSame($new->id, TicketDelegationAccess::current($ticket->fresh())->id);
    }

    public function test_no_se_usa_un_encargo_de_otro_ticket_aunque_se_conozca_su_id(): void
    {
        $context = $this->context();
        $first = $this->ticket($context);
        $second = $this->ticket($context);
        $firstDelegation = $this->grant($context, $first);
        $secondDelegation = $this->grant($context, $second);
        $before = $firstDelegation->getAttributes();
        $this->actingAs($context['delegate'])->post(route('tickets.delegations.propose', [$first, $secondDelegation]), [
            'version' => 1, 'solution_text' => 'Intento cruzado que no debe cambiar ninguno.',
        ])->assertNotFound();
        $this->assertSame($before, $firstDelegation->fresh()->getAttributes());
        $this->assertSame('active', $secondDelegation->fresh()->status);
    }

    public static function invalidatedContexts(): array
    {
        return [
            'encargado desactivado' => ['inactive'], 'encargado cambió departamento' => ['department'],
            'departamento desactivado' => ['department_inactive'], 'responsable TI desactivado' => ['responsible_inactive'],
            'responsable TI diferente' => ['responsible_changed'], 'nueva reapertura' => ['reopened'],
        ];
    }

    #[DataProvider('invalidatedContexts')]
    public function test_perder_contexto_valido_retira_acceso_sin_borrar_historial(string $change): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $before = $delegation->getAttributes();
        match ($change) {
            'inactive' => $context['delegate']->update(['is_active' => false]),
            'department' => $context['delegate']->update(['department_id' => $context['logistics']->id]),
            'department_inactive' => $context['sales']->update(['is_active' => false]),
            'responsible_inactive' => $context['agent']->update(['is_active' => false]),
            'responsible_changed' => $ticket->update(['assigned_to' => $context['otherAgent']->id]),
            'reopened' => $ticket->update(['reopened_count' => 1]),
        };
        $this->assertFalse(TicketDelegationAccess::isDelegate($context['delegate']->fresh(), $ticket->fresh()));
        $this->assertFalse(Gate::forUser($context['delegate']->fresh())->allows('view', $ticket->fresh()));
        $this->assertSame($before, $delegation->fresh()->getAttributes());
        $this->assertDatabaseCount('ticket_delegations', 1);
    }

    public function test_aprobado_es_lectura_sin_chat_y_ti_puede_retirarlo_incluso_despues_del_cierre(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        $this->propose($context, $ticket, $delegation);
        $this->actingAs($context['agent'])->post(route('tickets.delegations.approve', [$ticket, $delegation]), ['version' => 2])->assertSessionHas('success');
        $delegation->refresh();
        $this->actingAs($context['delegate'])->get(route('tickets.show', $ticket))->assertOk()->assertSee('Propuesta aprobada');
        $this->post(route('tickets.addComment', $ticket), $this->delegationComment($delegation))->assertForbidden();
        $ticket->update(['status' => Ticket::STATUS_CLOSED, 'closed_at' => now()]);
        $this->actingAs($context['agent'])->get(route('tickets.show', $ticket))->assertOk()->assertSee('Retirar este encargo');
        $before = $ticket->fresh()->getAttributes();
        $this->post(route('tickets.delegations.revoke', [$ticket, $delegation]), ['version' => 3])->assertSessionHas('success');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame('revoked', $delegation->fresh()->status);
        $this->actingAs($context['delegate'])->get(route('tickets.show', $ticket))->assertForbidden();
    }

    public function test_bandera_apagada_no_consulta_tabla_ni_cambia_flujo_legacy(): void
    {
        $context = $this->context();
        $ticket = $this->ticket($context);
        $delegation = $this->grant($context, $ticket);
        config(['ticket_delegation.enabled' => false]);
        $delegationQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$delegationQueries) {
            if (str_contains(strtolower($query->sql), 'ticket_delegations')) $delegationQueries[] = $query->sql;
        });
        $this->actingAs($context['delegate'])->get(route('tickets.show', $ticket))->assertForbidden();
        $this->actingAs($context['agent'])->get(route('tickets.show', $ticket))->assertOk()->assertDontSee('Colaboración por departamento');
        $this->post(route('tickets.addComment', $ticket), ['comment' => 'La atención habitual de TI sigue funcionando.'])->assertSessionHas('success');
        $this->post(route('tickets.delegations.store', $ticket), [
            'delegate_id' => $context['delegate']->id, 'instructions' => 'No habilitar encargos cuando la función está apagada.', 'expected_id' => $delegation->id, 'version' => 1,
        ])->assertNotFound();
        $this->assertSame([], $delegationQueries);
        $this->assertSame($context['agent']->id, $ticket->fresh()->assigned_to);
    }

    public function test_tabla_ausente_falla_cerrado_y_conserva_el_flujo_previo(): void
    {
        // Simular únicamente la comprobación del esquema: nunca ejecutar DDL
        // ni salir de la transacción de RefreshDatabase por este diagnóstico.
        Schema::partialMock()->shouldReceive('hasTable')->with('ticket_delegations')->andReturn(false);
        $context = $this->context();
        $ticket = $this->ticket($context);
        $users = $this->usersSnapshot();
        $delegationQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$delegationQueries) {
            if (str_contains(strtolower($query->sql), 'ticket_delegations')) $delegationQueries[] = $query->sql;
        });
        $this->actingAs($context['agent'])->get(route('tickets.show', $ticket))->assertOk()->assertDontSee('Colaboración por departamento');
        $this->post(route('tickets.addComment', $ticket), ['comment' => 'Responder sin la tabla nueva sigue siendo válido.'])->assertSessionHas('success');
        $this->post(route('tickets.delegations.store', $ticket), [
            'delegate_id' => $context['delegate']->id, 'instructions' => 'No debe activar un encargo sin su tabla.', 'expected_id' => 0, 'version' => 0,
        ])->assertNotFound();
        $this->actingAs($context['delegate'])->get(route('tickets.show', $ticket))->assertForbidden();
        $this->assertSame($users, $this->usersSnapshot());
        $this->assertSame([], $delegationQueries);
    }
}
