<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketAssignmentAccountRaceTest extends TestCase
{
    use RefreshDatabase;

    public static function lostAccess(): array
    {
        return ['cuenta desactivada' => [['is_active' => false]], 'rol ya no es soporte' => [['role' => 'user']]];
    }

    #[DataProvider('lostAccess')]
    public function test_revalidar_destino_dentro_del_guardado_impide_asignar_a_cuenta_que_perdio_acceso(array $change): void
    {
        Mail::fake();
        Notification::fake();
        $owner = User::factory()->create();
        $assigned = User::factory()->soporte()->create();
        $next = User::factory()->soporte()->create();
        $ticket = Ticket::create(['ticket_number' => 'RACE-'.fake()->unique()->numerify('########'), 'user_id' => $owner->id, 'assigned_to' => $assigned->id,
            'title' => 'Responsable preservado', 'description' => 'Caso existente.', 'status' => 'in_progress', 'priority' => 'medium']);
        $before = $ticket->fresh()->getAttributes();
        $injected = false;
        // La comprobación de exists devuelve válido; justo después se pierde
        // acceso, antes del UPDATE condicionado de la transferencia.
        DB::listen(function (QueryExecuted $query) use (&$injected, $next, $change) {
            if (!$injected && str_starts_with(strtolower($query->sql), 'select count(*)')
                && str_contains($query->sql, '`usuarios`') && in_array($next->id, $query->bindings)) {
                $injected = true;
                DB::table('usuarios')->where('id', $next->id)->update($change);
            }
        });
        $this->actingAs($assigned)->post(route('tickets.assignTo', $ticket), ['user_id' => $next->id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertTrue($injected);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }

    public function test_guardar_comentario_bloquea_autor_antes_del_ticket_como_la_asignacion(): void
    {
        Mail::fake();
        Notification::fake();
        // El creador también es soporte y puede ser destinatario de una
        // transferencia: reproduce los dos caminos que comparten su fila.
        $owner = User::factory()->soporte()->create();
        $assigned = User::factory()->soporte()->create();
        $ticket = Ticket::create(['ticket_number' => 'LOCK-ORDER', 'user_id' => $owner->id, 'assigned_to' => $assigned->id,
            'title' => 'Orden estable', 'status' => 'pending_user', 'priority' => 'medium']);
        $lockedTables = [];
        DB::listen(function (QueryExecuted $query) use (&$lockedTables) {
            if (str_contains(strtolower($query->sql), 'for update')) {
                if (str_contains($query->sql, '`usuarios`')) $lockedTables[] = 'usuarios';
                if (str_contains($query->sql, '`tickets`')) $lockedTables[] = 'tickets';
            }
        });
        $this->actingAs($owner)->post(route('tickets.addComment', $ticket), ['comment' => 'Confirmo el resultado para continuar.'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame(['usuarios', 'tickets'], $lockedTables);
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertSame(1, $ticket->comments()->count());
    }

    public function test_revalidar_autor_impide_guardar_si_la_cuenta_se_desactiva_durante_la_peticion(): void
    {
        Mail::fake();
        Notification::fake();
        $owner = User::factory()->create();
        $ticket = Ticket::create(['ticket_number' => 'AUTHOR-LOST', 'user_id' => $owner->id, 'assigned_to' => User::factory()->soporte()->create()->id,
            'title' => 'Conservar conversación', 'status' => 'pending_user', 'priority' => 'medium']);
        $before = $ticket->fresh()->getAttributes();
        $injected = false;
        DB::connection()->beforeExecuting(function ($query, $bindings) use (&$injected, $owner) {
            if (!$injected && str_contains($query, '`usuarios`') && str_contains(strtolower($query), 'for update')
                && in_array($owner->id, $bindings)) {
                $injected = true;
                DB::table('usuarios')->where('id', $owner->id)->update(['is_active' => false]);
            }
        });
        $this->actingAs($owner)->post(route('tickets.addComment', $ticket), ['comment' => 'No debe guardarse tras perder acceso.'])
            ->assertSessionHasErrors('comment');
        $this->assertTrue($injected);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
        $this->assertDatabaseCount('notificaciones', 0);
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }
}
