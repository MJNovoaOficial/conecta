<?php

namespace Tests\Feature;

use App\Jobs\CloseResolvedTicketsJob;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketReopenVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        $this->travelTo(now()->startOfSecond());
    }

    private function ticket(array $extra = []): Ticket
    {
        return Ticket::create(array_replace([
            'ticket_number' => 'REOPEN-'.Str::uuid(), 'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->soporte()->create()->id, 'title' => 'Caso para revisar la solución',
            'description' => 'Descripción original del caso.', 'priority' => 'medium',
            'status' => Ticket::STATUS_RESOLVED, 'resolved_at' => now(), 'solution_text' => 'Solución registrada que se conserva.',
        ], $extra));
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        return new \DOMXPath($document);
    }

    public function test_requester_assigned_support_and_admin_get_one_reopen_form_in_full_and_quick_views(): void
    {
        $ticket = $this->ticket();
        $admin = User::factory()->administrador()->create();
        foreach ([$ticket->user, $ticket->assignedTo, $admin] as $actor) {
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $response = $this->actingAs($actor)->get(route($route, $ticket))->assertOk()
                    ->assertSee('Reabrir ticket')->assertSee(route('tickets.reopen', $ticket), false);
                $xpath = $this->xpath($response->getContent());
                $this->assertSame(1, $xpath->query('//section[@data-ticket-reopen]//form')->length);
                $this->assertSame(1, $xpath->query('//form[contains(@action,"/reopen")]')->length);
                $direct = $actor->can('manage', $ticket);
                $this->assertSame($direct ? 1 : 0, $xpath->query('//form[@data-ticket-reopen-direct]')->length);
                $this->assertSame($direct ? 0 : 1, $xpath->query('//section[@data-ticket-reopen]//textarea')->length);
                $this->assertSame(0, $xpath->query('//details[@data-ticket-reopen-reason][@open]')->length);
            }
        }
        foreach ([$ticket->assignedTo, $admin] as $staffRequester) {
            $own = $this->ticket(['user_id' => $staffRequester->id]);
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $this->actingAs($staffRequester)->get(route($route, $own))->assertOk()->assertSee(route('tickets.reopen', $own), false);
            }
        }
    }

    public function test_another_support_cannot_see_or_use_reopening_and_no_ticket_data_changes(): void
    {
        $ticket = $this->ticket();
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs(User::factory()->soporte()->create());
        foreach (['tickets.show', 'tickets.panel'] as $route) {
            $this->get(route($route, $ticket))->assertOk()->assertDontSee('data-ticket-reopen', false)
                ->assertDontSee(route('tickets.reopen', $ticket), false);
        }
        $this->post(route('tickets.reopen', $ticket), ['staff_reopening' => true])->assertForbidden();
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_button_is_absent_for_active_and_definitively_closed_tickets(): void
    {
        $admin = User::factory()->administrador()->create();
        foreach ([Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING_USER, Ticket::STATUS_FORWARDED, Ticket::STATUS_CLOSED] as $state) {
            $ticket = $this->ticket(['status' => $state]);
            $before = $ticket->fresh()->getAttributes();
            foreach (['tickets.show', 'tickets.panel'] as $route) {
                $this->actingAs($admin)->get(route($route, $ticket))->assertOk()->assertDontSee('data-ticket-reopen', false);
            }
            $this->assertSame($before, $ticket->fresh()->getAttributes());
        }
    }

    public function test_guest_keeps_its_token_form_even_with_an_unrelated_regular_session(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest@example.invalid', 'guest_token' => Str::random(40)]);
        $url = route('tickets.guest.show', $ticket->guest_token);
        foreach ([null, User::factory()->create()] as $actor) {
            if ($actor) $this->actingAs($actor);
            else Auth::logout();
            $response = $this->get($url)->assertOk()->assertSee('Reabrir ticket')
                ->assertSee(route('tickets.guest.reopen', $ticket->guest_token), false)->assertDontSee(route('tickets.reopen', $ticket), false);
            $this->assertSame(1, $this->xpath($response->getContent())->query('//form[contains(@action,"/reopen")]')->length);
            $this->assertSame(1, $this->xpath($response->getContent())->query('//section[@data-ticket-reopen]//textarea')->length);
        }
        $ticket->update(['status' => Ticket::STATUS_CLOSED]);
        $this->get($url)->assertOk()->assertDontSee('data-ticket-reopen', false);
    }

    public function test_invalid_reason_keeps_the_form_open_with_escaped_input_and_no_reopening(): void
    {
        $ticket = $this->ticket();
        $url = route('tickets.show', $ticket);
        $reason = '<b>no</b>';
        $this->actingAs($ticket->user)->from($url)->post(route('tickets.reopen', $ticket), ['motivo' => $reason])
            ->assertRedirect($url)->assertSessionHasErrors('motivo');
        $response = $this->get($url)->assertOk()->assertSee($reason)->assertDontSee($reason, false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//details[@data-ticket-reopen-reason][@open]')->length);
        $this->assertSame($reason, $xpath->query('//details[@data-ticket-reopen-reason]//textarea')->item(0)->textContent);
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
    }

    public function test_authorized_staff_can_reopen_preserving_assignment_solution_and_existing_closure_flow(): void
    {
        foreach (['support', 'admin'] as $role) {
            $ticket = $this->ticket();
            $actor = $role === 'support' ? $ticket->assignedTo : User::factory()->administrador()->create();
            $this->actingAs($actor)->post(route('tickets.reopen', $ticket), [])
                ->assertSessionHas('success');
            $fresh = $ticket->fresh();
            $this->assertSame(Ticket::STATUS_IN_PROGRESS, $fresh->status);
            $this->assertSame($ticket->assigned_to, $fresh->assigned_to);
            $this->assertSame($ticket->solution_text, $fresh->solution_text);
            $this->assertNull($fresh->resolved_at);
            $this->assertSame(1, (int) $fresh->reopened_count);
            $this->assertSame(1, $fresh->history()->where('action', 'reopened')->where('user_id', $actor->id)->count());
            $this->assertSame(1, $fresh->comments()->count());
            $note = $role === 'admin' ? 'Ticket reabierto por administración para continuar la atención.' : 'Ticket reabierto por soporte para continuar la atención.';
            $this->assertSame($note, $fresh->comments()->first()->comment);
            $audit = AuditLog::where('action', 'ticket.reopened')->where('model_id', $ticket->id)->firstOrFail();
            $this->assertSame($actor->id, $audit->user_id);
            $this->assertSame($note, $audit->details['motivo']);
            Notification::assertSentTo($ticket->assignedTo, TicketUpdatedNotification::class, function ($notification, $channels, $recipient) {
                $message = implode(' ', $notification->toMail($recipient)->introLines);
                return str_contains($message, 'equipo de soporte reabrió') && !str_contains($message, 'El solicitante reabrió');
            });
            $this->post(route('tickets.reopen', $ticket), [])->assertSessionHas('error');
            $this->assertSame(1, $ticket->comments()->count());
            $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('Ticket reabierto')
                ->assertDontSee('Reabierto: la solución no resolvió el problema');
            $this->travel(2)->hours();
            (new CloseResolvedTicketsJob)->handle();
            $this->assertSame(Ticket::STATUS_IN_PROGRESS, $ticket->fresh()->status);
            $this->withSession(['last_activity_at' => now()]);
        }
    }

    public function test_requesters_and_guests_still_need_a_reason_and_cannot_claim_staff_access(): void
    {
        foreach ([User::factory()->create(), User::factory()->soporte()->create()] as $requester) {
            $ticket = $this->ticket(['user_id' => $requester->id]);
            $before = $ticket->fresh()->getAttributes();
            $this->actingAs($requester)->post(route('tickets.reopen', $ticket), ['staff_reopening' => true])
                ->assertSessionHasErrors('motivo');
            $response = $this->get(route('tickets.show', $ticket))->assertOk();
            $this->assertSame(1, $this->xpath($response->getContent())->query('//section[@data-ticket-reopen]//textarea')->length);
            $this->assertSame($before, $ticket->fresh()->getAttributes());
        }
        Auth::logout();
        $guest = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado', 'guest_email' => 'guest@example.invalid', 'guest_token' => Str::random(40)]);
        $this->post(route('tickets.guest.reopen', $guest->guest_token), ['staff_reopening' => true])->assertSessionHasErrors('motivo');
        $this->assertSame(Ticket::STATUS_RESOLVED, $guest->fresh()->status);
        $this->assertDatabaseCount('comentarios_ticket', 0);
        $this->assertDatabaseCount('historial_ticket', 0);
    }

    public function test_staff_can_keep_a_reason_from_an_older_form_and_cannot_reopen_a_closed_ticket_without_one(): void
    {
        $ticket = $this->ticket();
        $reason = 'Motivo escrito en un formulario anterior que se conserva.';
        $this->actingAs($ticket->assignedTo)->post(route('tickets.reopen', $ticket), ['motivo' => $reason])->assertSessionHas('success');
        $this->assertSame($reason, $ticket->comments()->first()->comment);
        $closed = $this->ticket(['status' => Ticket::STATUS_CLOSED, 'closed_at' => now()]);
        $before = $closed->fresh()->getAttributes();
        foreach ([$closed->assignedTo, User::factory()->administrador()->create()] as $actor) {
            $this->actingAs($actor)->post(route('tickets.reopen', $closed), [])->assertSessionHas('error');
        }
        $this->assertSame($before, $closed->fresh()->getAttributes());
        $this->assertSame(0, $closed->history()->count());
    }
}
