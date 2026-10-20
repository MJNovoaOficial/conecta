<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TicketDelegationAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public static function unavailable(): array
    {
        return ['desactivado' => [false], 'tabla pendiente de instalar' => [true]];
    }

    #[DataProvider('unavailable')]
    public function test_todas_las_rutas_fallan_cerradas_sin_binding_a_tabla_no_disponible(bool $enabled): void
    {
        Mail::fake();
        Notification::fake();
        config(['ticket_delegation.enabled' => $enabled]);
        if ($enabled) Schema::partialMock()->shouldReceive('hasTable')->with('ticket_delegations')->andReturn(false);
        $admin = User::factory()->administrador()->create();
        $ticket = Ticket::create(['ticket_number' => 'SAFE-UNAVAILABLE', 'user_id' => User::factory()->create()->id,
            'assigned_to' => $admin->id, 'status' => 'in_progress', 'priority' => 'medium', 'title' => 'Caso conservado'])->fresh();
        $before = $ticket->getAttributes();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            if (str_contains(strtolower($query->sql), 'from `ticket_delegations`')) $queries[] = $query->sql;
        });
        $this->actingAs($admin);
        foreach (['revoke', 'propose', 'changes', 'approve'] as $action) {
            $this->post(route('tickets.delegations.'.$action, [$ticket, 999]), ['version' => 1])->assertNotFound();
        }
        $this->post(route('tickets.delegations.store', $ticket), [])->assertNotFound();
        $this->get(route('tickets.show', $ticket))->assertOk()->assertDontSee('Colaboración por departamento');
        $this->assertSame([], $queries);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        Notification::assertNothingSent();
        Mail::assertNothingOutgoing();
    }
}
