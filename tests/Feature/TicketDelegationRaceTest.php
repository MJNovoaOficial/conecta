<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketDelegation;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketDelegationRaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ticket_delegation.enabled' => true]);
        Mail::fake();
        Notification::fake();
        $this->travelTo(now()->startOfSecond());
    }

    private function scenario(bool $withDelegation = true, string $status = 'active'): array
    {
        $ti = Department::create(['name' => 'TI', 'is_active' => true]);
        $sales = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $owner = User::factory()->create(['department_id' => $sales->id]);
        $responsible = User::factory()->soporte()->create(['department_id' => $ti->id]);
        $admin = User::factory()->administrador()->create(['department_id' => $ti->id]);
        $delegate = User::factory()->create(['department_id' => $sales->id]);
        $ticket = Ticket::create([
            'ticket_number' => 'DELEG-RACE-'.fake()->unique()->numerify('##########'),
            'user_id' => $owner->id, 'assigned_to' => $responsible->id, 'department_id' => $sales->id,
            'title' => 'Caso existente', 'description' => 'Datos que deben permanecer intactos.',
            'status' => Ticket::STATUS_IN_PROGRESS, 'priority' => 'medium', 'reopened_count' => 0,
        ])->fresh();
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $owner->id, 'comment' => 'Conversación anterior que se conserva.']);
        TicketHistory::create(['ticket_id' => $ticket->id, 'user_id' => $responsible->id, 'action' => 'assigned',
            'old_value' => null, 'new_value' => (string) $responsible->id, 'field_name' => 'assigned_to']);
        $delegation = $withDelegation ? TicketDelegation::create([
            'ticket_id' => $ticket->id, 'delegate_id' => $delegate->id, 'responsible_id' => $responsible->id,
            'delegated_by' => $responsible->id, 'department_id' => $sales->id,
            'instructions' => 'Revisar este caso sin alterar otros registros.', 'status' => $status,
            'version' => $status === 'submitted' ? 2 : 1, 'reopened_count' => 0,
            'proposal_text' => $status === 'submitted' ? 'Se corrigió la configuración y se verificó su funcionamiento.' : null,
            'submitted_at' => $status === 'submitted' ? now() : null,
        ])->fresh() : null;
        return compact('ti', 'sales', 'owner', 'responsible', 'admin', 'delegate', 'ticket', 'delegation');
    }

    private function rows(string $table): array
    {
        return DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public static function staleComment(): array
    {
        return ['revocado mientras responde' => ['revoked'], 'misma persona con versión nueva' => ['active']];
    }

    #[DataProvider('staleComment')]
    public function test_revalidar_encargo_bajo_lock_rechaza_comentario_autorizado_con_version_anterior(string $newStatus): void
    {
        extract($this->scenario());
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforeComments = $this->rows('comentarios_ticket');
        $beforeHistory = $this->rows('historial_ticket');
        $beforeUsers = $this->rows('usuarios');
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings) use (&$injected, $ticket, $delegation, $newStatus) {
            if (!$injected && str_contains($query, 'from `tickets`') && str_contains(strtolower($query), 'for update')
                && in_array($ticket->id, $bindings, true)) {
                $injected = true;
                // La autorización HTTP ya pasó. Simular el cambio antes de
                // adquirir el ticket, sin borrar la fila o su historial.
                DB::table('ticket_delegations')->where('id', $delegation->id)
                    ->update(['status' => $newStatus, 'version' => 2]);
            }
        });
        $this->actingAs($delegate)->post(route('tickets.addComment', $ticket), [
            'comment' => 'Respuesta desde una pestaña que ya quedó antigua.',
            'delegation_id' => $delegation->id, 'delegation_version' => 1,
        ])->assertRedirect()->assertSessionHasErrors('comment');
        $this->assertTrue($injected);
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforeComments, $this->rows('comentarios_ticket'));
        $this->assertSame($beforeHistory, $this->rows('historial_ticket'));
        $this->assertSame($beforeUsers, $this->rows('usuarios'));
        $this->assertSame($newStatus, $delegation->fresh()->status);
        $this->assertSame(2, $delegation->fresh()->version);
        $this->assertDatabaseCount('notificaciones', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }

    public static function changedRecipient(): array
    {
        return ['se desactivó' => ['inactive'], 'se cambió al departamento TI' => ['department']];
    }

    #[DataProvider('changedRecipient')]
    public function test_grant_revalida_persona_bajo_lock_y_no_guarda_encargo_con_contexto_obsoleto(string $change): void
    {
        extract($this->scenario(false));
        $ticket->update(['status' => Ticket::STATUS_PENDING_USER, 'last_response_request_at' => now(), 'response_deadline_at' => now()->addHours(2)]);
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforeComments = $this->rows('comentarios_ticket');
        $beforeHistory = $this->rows('historial_ticket');
        $beforeUsers = $this->rows('usuarios');
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings) use (&$injected, $delegate, $ti, $change) {
            // whereKey(array<int>) usa IN de enteros literales en algunas
            // versiones del builder; no siempre deja IDs en bindings.
            $ids = $bindings;
            if (preg_match_all('/`(?:usuarios`\.)?`?id`?\s+in\s*\(\s*(\d+(?:\s*,\s*\d+)*)\s*\)/i', $query, $matches)) {
                foreach ($matches[1] as $list) {
                    foreach (preg_split('/\s*,\s*/', $list) as $id) $ids[] = (int) $id;
                }
            }
            if (!$injected && str_contains($query, 'from `usuarios`') && str_contains(strtolower($query), 'for update')
                && in_array($delegate->id, $ids, true)) {
                $injected = true;
                DB::table('usuarios')->where('id', $delegate->id)
                    ->update($change === 'inactive' ? ['is_active' => false] : ['department_id' => $ti->id]);
            }
        });
        $response = $this->actingAs($responsible)->post(route('tickets.delegations.store', $ticket), [
            'delegate_id' => $delegate->id, 'instructions' => 'Este encargo no debe guardarse si la cuenta cambió.',
            'expected_id' => 0, 'version' => 0,
        ]);
        $this->assertTrue($injected, 'La carrera debe inyectarse antes del SELECT de usuarios con bloqueo.');
        $response->assertRedirect()->assertSessionHasErrors('delegate_id');
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforeComments, $this->rows('comentarios_ticket'));
        $this->assertSame($beforeHistory, $this->rows('historial_ticket'));
        // La inyección comparte esta conexión/transacción de pruebas: al
        // rechazarse el grant también se revierte su mutación simulada. No es
        // una prueba de dos conexiones ni una alteración persistente de cuentas.
        $this->assertSame($beforeUsers, $this->rows('usuarios'));
        $this->assertSame('user', $delegate->fresh()->role);
        $this->assertSame($responsible->id, $ticket->fresh()->assigned_to);
        $this->assertDatabaseCount('ticket_delegations', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('notificaciones', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }

    public function test_fallo_de_auditoria_final_revierte_aprobacion_resolucion_e_historial_sin_notificar(): void
    {
        extract($this->scenario(true, 'submitted'));
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforeDelegation = $delegation->fresh()->getAttributes();
        $beforeComments = $this->rows('comentarios_ticket');
        $beforeHistory = $this->rows('historial_ticket');
        $beforeUsers = $this->rows('usuarios');
        $beforeAudits = $this->rows('audit_logs');
        $originalDispatcher = AuditLog::getEventDispatcher();
        $scopedDispatcher = clone $originalDispatcher;
        AuditLog::setEventDispatcher($scopedDispatcher);
        $failed = false;
        AuditLog::creating(function (AuditLog $audit) use (&$failed) {
            if ($audit->action === 'ticket.resolved') {
                $failed = true;
                throw new \RuntimeException('Fallo simulado únicamente en la auditoría final de resolución.');
            }
        });
        try {
            $this->actingAs($responsible)->post(route('tickets.delegations.approve', [$ticket, $delegation]), ['version' => 2])
                ->assertStatus(500);
        } finally {
            AuditLog::setEventDispatcher($originalDispatcher);
        }
        $this->assertTrue($failed);
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforeDelegation, $delegation->fresh()->getAttributes());
        $this->assertSame($beforeComments, $this->rows('comentarios_ticket'));
        $this->assertSame($beforeHistory, $this->rows('historial_ticket'));
        $this->assertSame($beforeUsers, $this->rows('usuarios'));
        $this->assertSame($beforeAudits, $this->rows('audit_logs'));
        $this->assertDatabaseCount('notificaciones', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }

    public function test_down_se_niega_con_encargos_y_preserva_tabla_filas_y_usuarios_sin_ejecutar_ddl(): void
    {
        extract($this->scenario());
        $beforeTicket = $ticket->fresh()->getAttributes();
        $beforeDelegations = $this->rows('ticket_delegations');
        $beforeComments = $this->rows('comentarios_ticket');
        $beforeHistory = $this->rows('historial_ticket');
        $beforeUsers = $this->rows('usuarios');
        $ddl = [];
        DB::connection()->beforeExecuting(function ($query) use (&$ddl) {
            if (preg_match('/^\s*(?:drop|alter|rename|truncate|create)\b/i', $query)) {
                $ddl[] = $query;
                throw new \LogicException('La prueba prohíbe ejecutar DDL.');
            }
        });
        $migration = require database_path('migrations/2026_10_19_210000_create_ticket_delegations_table.php');
        $rejected = false;
        try {
            $migration->down();
        } catch (\RuntimeException $e) {
            $rejected = true;
            $this->assertStringContainsString('contiene registros', $e->getMessage());
        }
        $this->assertTrue($rejected);
        $this->assertSame([], $ddl);
        $this->assertTrue(Schema::hasTable('ticket_delegations'));
        $this->assertSame($beforeTicket, $ticket->fresh()->getAttributes());
        $this->assertSame($beforeDelegations, $this->rows('ticket_delegations'));
        $this->assertSame($beforeComments, $this->rows('comentarios_ticket'));
        $this->assertSame($beforeHistory, $this->rows('historial_ticket'));
        $this->assertSame($beforeUsers, $this->rows('usuarios'));
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }
}
