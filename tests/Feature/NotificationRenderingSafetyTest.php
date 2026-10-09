<?php

namespace Tests\Feature;

use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class NotificationRenderingSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_ticket_invitado_sigue_notificando_y_el_dropdown_escapa_campos_sin_alterar_datos(): void
    {
        Mail::fake(); Notification::fake(); RateLimiter::clear('guest_ticket:127.0.0.1');
        $support = User::factory()->soporte()->create();
        $admin = User::factory()->administrador()->create();
        $title = '<img src="/missing-test-image" onerror="window.__notifSafety=1">';
        $this->post(route('tickets.guest.store'), ['guest_name' => 'Invitado', 'guest_email' => 'safe-test@example.test', 'title' => $title, 'description' => 'Solicitud pública válida.'])->assertSessionHasNoErrors();
        $ticket = Ticket::firstOrFail();
        $this->assertSame($title, $ticket->title);
        foreach ([$support, $admin] as $actor) {
            $notice = Notificacion::where('user_id', $actor->id)->where('ticket_id', $ticket->id)->firstOrFail();
            $this->actingAs($actor)->get(route('notifications.recent'))->assertOk()->assertJsonFragment(['body' => $title]);
            $response = $this->get(route('tickets.index'))->assertOk();
            $response->assertSee('${escapeNotificationText(n.title)}', false)
                ->assertSee('${escapeNotificationText(n.body)}', false)
                ->assertDontSee('${n.title}', false)->assertDontSee("\${n.body || ''}", false);
            $this->get(route('notifications.index'))->assertOk()->assertSee($title, true)->assertDontSee($title, false);
            $this->post(route('notifications.read', $notice))->assertRedirect(route('tickets.show', $ticket));
            $this->assertNotNull($notice->fresh()->read_at);
        }
        $this->assertSame($title, $ticket->fresh()->title);
        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseCount('notificaciones', 2);
    }
}
