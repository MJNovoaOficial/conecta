<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketChatAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function ticket(?User $owner, ?User $agent = null): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'CHAT-GATE-'.fake()->unique()->numerify('########'),
            'user_id' => $owner?->id, 'assigned_to' => $agent?->id,
            'title' => 'Solicitud de prueba', 'description' => 'Detalles conservados.',
            'status' => Ticket::STATUS_OPEN, 'priority' => 'medium',
            'guest_name' => $owner ? null : 'Invitado', 'guest_email' => $owner ? null : 'guest-chat@example.test',
            'guest_token' => $owner ? null : str_repeat('z', 40),
        ]);
    }

    private function commentForms(string $html): int
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        return (new \DOMXPath($dom))->query('//form[@data-comment-submit]')->length;
    }

    public function test_sin_asignacion_ningun_rol_puede_escribir_ni_solicitar_informacion(): void
    {
        $owner = User::factory()->create();
        $support = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $ticket = $this->ticket($owner);
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $owner->id, 'comment' => 'Historial previo.']);
        $before = $ticket->fresh()->getAttributes();
        foreach ([$owner, $support, $admin] as $actor) {
            foreach ([[], ['request_info' => 1], ['is_internal' => 1]] as $option) {
                $response = $this->actingAs($actor)->post(route('tickets.addComment', $ticket), ['comment' => 'No debe guardarse.'] + $option);
                if ($actor->isAdmin()) $response->assertSessionHasErrors('comment');
                else $response->assertForbidden();
                $this->assertSame($before, $ticket->fresh()->getAttributes());
                $this->assertSame(1, $ticket->comments()->count());
                $this->assertDatabaseCount('historial_ticket', 0);
                $this->assertDatabaseCount('notificaciones', 0);
            }
        }
        Notification::assertNothingSent();
    }

    public function test_sin_asignacion_show_y_panel_conservan_historial_pero_no_formularios_de_comentario(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticket($owner);
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $owner->id, 'comment' => 'Mensaje conservado.']);
        foreach ([$owner, User::factory()->soporte()->create(), User::factory()->administrador()->create()] as $actor) {
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $response = $this->actingAs($actor)->get(route($route, $ticket))->assertOk()
                    ->assertSee('La conversación se habilitará')->assertSee('Mensaje conservado.');
                $this->assertSame(0, $this->commentForms($response->getContent()));
            }
        }
    }

    public function test_tomar_el_ticket_habilita_solicitante_asignado_y_supervision_admin_pero_no_otro_soporte(): void
    {
        $owner = User::factory()->create();
        $assigned = User::factory()->soporte()->create();
        $other = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $ticket = $this->ticket($owner);
        $this->actingAs($assigned)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        foreach ([$owner, $assigned, $admin] as $actor) {
            $this->actingAs($actor)->post(route('tickets.addComment', $ticket), ['comment' => 'Respuesta autorizada.'])->assertSessionHasNoErrors();
        }
        $this->assertSame(3, $ticket->comments()->count());
        foreach ([[], ['is_internal' => 1], ['request_info' => 1]] as $option) {
            $this->actingAs($other)->post(route('tickets.addComment', $ticket), ['comment' => 'No autorizado.'] + $option)->assertForbidden();
        }
        $this->assertSame(3, $ticket->comments()->count());
        foreach (['tickets.show', 'tickets.panel'] as $route) {
            $response = $this->actingAs($other)->get(route($route, $ticket))->assertOk()->assertSee('otro agente');
            $this->assertSame(0, $this->commentForms($response->getContent()));
            $response = $this->actingAs($assigned)->get(route($route, $ticket))->assertOk();
            $this->assertGreaterThan(0, $this->commentForms($response->getContent()));
        }
    }

    public function test_derivar_bloquea_el_chat_hasta_que_el_nuevo_agente_tome_y_el_anterior_no_puede_intervenir(): void
    {
        $owner = User::factory()->create();
        $first = User::factory()->soporte()->create();
        $next = User::factory()->soporte()->create();
        $ticket = $this->ticket($owner, $first);
        $department = \App\Models\Department::create(['name' => 'Destino chat', 'is_active' => true]);
        $this->actingAs($first)->post(route('tickets.forward', $ticket), ['department_id' => $department->id])->assertSessionHas('success');
        $this->actingAs($owner)->post(route('tickets.addComment', $ticket), ['comment' => 'Todavía bloqueado.'])->assertForbidden();
        $this->assertSame(0, $ticket->comments()->count());
        $this->actingAs($next)->post(route('tickets.selfAssign', $ticket))->assertSessionHas('success');
        $this->actingAs($first)->post(route('tickets.addComment', $ticket), ['comment' => 'Ya no soy responsable.'])->assertForbidden();
        $this->actingAs($next)->post(route('tickets.addComment', $ticket), ['comment' => 'Asumo la atención.'])->assertSessionHasNoErrors();
        $this->assertSame(1, $ticket->comments()->count());
    }

    public function test_el_invitado_puede_crear_sin_login_y_responde_solo_despues_de_la_asignacion(): void
    {
        $ticket = $this->ticket(null);
        $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()->assertSee('La conversación se habilitará');
        $this->post(route('tickets.guest.comment', $ticket->guest_token), ['comment' => 'Antes de asignar.'])->assertSessionHasErrors('comment');
        $this->assertSame(0, $ticket->comments()->count());
        $ticket->update(['assigned_to' => User::factory()->soporte()->create()->id, 'status' => Ticket::STATUS_PENDING_USER, 'response_deadline_at' => now()->addHours(2)]);
        $this->flushSession();
        $response = $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk();
        $this->assertSame(1, $this->commentForms($response->getContent()));
        $this->post(route('tickets.guest.comment', $ticket->guest_token), ['comment' => 'Información del solicitante.'])->assertSessionHasNoErrors();
        $this->assertSame(1, $ticket->comments()->count());
        $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->user_responded_at);
    }

    public function test_una_asignacion_a_un_usuario_comun_no_habilita_el_chat(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticket($owner, User::factory()->create());
        $this->assertFalse($ticket->hasAssignedSupport());
        $this->actingAs($owner)->post(route('tickets.addComment', $ticket), ['comment' => 'No hay agente válido.'])->assertForbidden();
        $this->actingAs(User::factory()->administrador()->create())->post(route('tickets.addComment', $ticket), ['comment' => 'Tampoco por admin.'])->assertSessionHasErrors('comment');
        $this->assertSame(0, $ticket->comments()->count());
    }
}
