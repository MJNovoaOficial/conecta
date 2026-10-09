<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketConversationTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(?User $owner, string $status): Ticket
    {
        return Ticket::create([
            'ticket_number' => 'CHAT-'.fake()->unique()->numerify('########'),
            'user_id' => $owner?->id, 'title' => 'Consulta de correo',
            'description' => 'Detalles de la solicitud.', 'status' => $status, 'priority' => 'medium',
            'guest_name' => $owner ? null : 'Invitado',
            'guest_email' => $owner ? null : 'chat-test@example.test',
            'guest_token' => $owner ? null : str_repeat('g',40),
        ]);
    }

    public function test_un_ticket_cerrado_no_acepta_respuestas_de_ningun_rol_ni_cambia_su_estado(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $assigned = User::factory()->soporte()->create();
        $ticket = $this->ticket($owner, Ticket::STATUS_CLOSED);
        $ticket->update(['assigned_to' => $assigned->id]);
        TicketComment::create(['ticket_id' => $ticket->id, 'user_id' => $owner->id, 'comment' => 'Mensaje previo que debe conservarse.']);
        foreach ([$owner, $assigned, User::factory()->administrador()->create()] as $actor) {
            $this->actingAs($actor)->from(route('tickets.show',$ticket))
                ->post(route('tickets.addComment',$ticket), ['comment' => 'Excelente'])
                ->assertSessionHasErrors('comment')->assertRedirect(route('tickets.show',$ticket));
            $this->assertSame(1,$ticket->comments()->count());
            $this->assertSame(Ticket::STATUS_CLOSED,$ticket->fresh()->status);
        }
        $this->assertDatabaseCount('notificaciones',0);
    }

    public function test_el_historial_cerrado_se_ve_sin_formulario_de_respuesta_incluso_para_admin(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticket($owner, Ticket::STATUS_CLOSED);
        TicketComment::create(['ticket_id' => $ticket->id,'user_id' => $owner->id,'comment' => 'Mensaje previo conservado.']);
        foreach ([$owner,User::factory()->soporte()->create(),User::factory()->administrador()->create()] as $actor) {
            foreach (['tickets.show','tickets.panel'] as $route) {
                $response = $this->actingAs($actor)->get(route($route,$ticket))->assertOk()
                    ->assertSee('Mensaje previo conservado.')->assertSee('solo está disponible para consulta');
                $document = new \DOMDocument();
                @$document->loadHTML($response->getContent());
                $this->assertSame(0,(new \DOMXPath($document))->query('//form[@data-comment-submit]')->length);
            }
        }
    }

    public function test_admin_no_puede_reactivar_el_chat_cerrado_mediante_solicitud_de_informacion(): void
    {
        Notification::fake();
        $ticket = $this->ticket(User::factory()->create(),Ticket::STATUS_CLOSED);
        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('tickets.addComment',$ticket),['comment'=>'Necesitamos información adicional.','request_info'=>1])
            ->assertSessionHasErrors('comment');
        $this->assertSame(Ticket::STATUS_CLOSED,$ticket->fresh()->status);
        $this->assertSame(0,$ticket->comments()->count());
        $this->assertDatabaseCount('historial_ticket',0);
    }

    public function test_las_respuestas_de_tickets_abiertos_siguen_funcionando_para_los_tres_roles(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        foreach ([$owner,User::factory()->soporte()->create(),User::factory()->administrador()->create()] as $actor) {
            $ticket = $this->ticket($owner,Ticket::STATUS_OPEN);
            $ticket->update(['assigned_to' => $actor->isSupport() || $actor->isAdmin() ? $actor->id : User::factory()->soporte()->create()->id]);
            $this->actingAs($actor)->post(route('tickets.addComment',$ticket),['comment'=>'Información adicional de prueba.'])->assertSessionHasNoErrors();
            $this->assertSame(1,$ticket->comments()->count());
        }
    }

    public function test_el_invitado_conserva_su_acceso_pero_no_responde_un_ticket_cerrado(): void
    {
        $ticket = $this->ticket(null,Ticket::STATUS_CLOSED);
        $this->get(route('tickets.guest.show',$ticket->guest_token))->assertOk()->assertSee('solo está disponible para consulta');
        $this->post(route('tickets.guest.comment',$ticket->guest_token),['comment'=>'Intento después del cierre.'])->assertSessionHasErrors('comment');
        $this->assertSame(0,$ticket->comments()->count());
    }
}
