<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketDelegationWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketDelegationLockScopeTest extends TestCase
{
    // Two real connections need committed fixtures, not RefreshDatabase's outer
    // transaction. TestCase validates the disposable database before migration.
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
        $this->beforeApplicationDestroyed(function () {
            RefreshDatabaseState::$migrated = false;
            DB::purge('delegation_lock_probe');
        });
    }

    public function test_encargo_bloquea_participantes_pero_no_usuarios_ajenos_ni_nuevos_registros(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requiere dos conexiones MySQL/InnoDB reales.');
        }
        config(['ticket_delegation.enabled' => true]);
        $ti = Department::create(['name' => 'TI', 'is_active' => true]);
        $sales = Department::create(['name' => 'Ventas', 'is_active' => true]);
        $unrelated = User::factory()->create(['department_id' => $sales->id]);
        $delegate = User::factory()->create(['department_id' => $sales->id]);
        $responsible = User::factory()->soporte()->create(['department_id' => $ti->id]);
        User::factory()->administrador()->create(['department_id' => $ti->id]);
        $ticket = Ticket::create([
            'ticket_number' => 'LOCK-SCOPE-ENCARGO', 'user_id' => $unrelated->id,
            'assigned_to' => $responsible->id, 'department_id' => $sales->id,
            'title' => 'Comprobar bloqueos acotados', 'status' => Ticket::STATUS_IN_PROGRESS,
            'priority' => 'medium', 'reopened_count' => 0,
        ])->fresh();
        $usersBefore = DB::table('usuarios')->orderBy('id')->get()->toJson();
        $newUser = User::factory()->make(['department_id' => $sales->id])->getAttributes();
        config(['database.connections.delegation_lock_probe' => config('database.connections.'.config('database.default'))]);
        $probe = DB::connection('delegation_lock_probe');
        $probe->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $probe->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $primary = DB::connection();
        $primary->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $attempt = function (\Closure $operation) use ($probe): ?int {
            $probe->beginTransaction();
            try {
                $operation($probe);
                return null;
            } catch (QueryException $e) {
                return (int) ($e->errorInfo[1] ?? 0);
            } finally {
                $probe->rollBack();
            }
        };
        $primary->beginTransaction();
        try {
            TicketDelegationWorkflow::lockContext($ticket, $responsible, [$delegate->id]);
            $unrelatedError = $attempt(fn ($connection) => $connection->table('usuarios')
                ->where('id', $unrelated->id)->update(['name' => 'Cambio ajeno que se revierte']));
            $registrationError = $attempt(fn ($connection) => $connection->table('usuarios')->insert($newUser));
            $participantError = $attempt(fn ($connection) => $connection->table('usuarios')
                ->where('id', $delegate->id)->update(['is_active' => false]));
        } finally {
            $primary->rollBack();
        }
        $this->assertSame($usersBefore, DB::table('usuarios')->orderBy('id')->get()->toJson());
        $this->assertNull($unrelatedError, 'Un encargo no debe hacer esperar a una cuenta ajena.');
        $this->assertNull($registrationError, 'Un encargo no debe bloquear el registro de otro usuario.');
        $this->assertSame(1205, $participantError, 'La cuenta participante debe permanecer protegida hasta terminar la transacción.');
    }
}
