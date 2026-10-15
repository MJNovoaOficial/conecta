<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketSolutionPlacementTest extends TestCase
{
    use RefreshDatabase;

    public function test_solucion_queda_despues_de_la_conversacion_en_detalle_y_panel(): void
    {
        $owner = User::factory()->create();
        $support = User::factory()->soporte()->create();
        $ticket = Ticket::create(['ticket_number' => 'POSITION-'.Str::uuid(), 'user_id' => $owner->id,
            'assigned_to' => $support->id, 'title' => 'Revisar conexión', 'description' => 'Descripción original',
            'priority' => 'medium', 'status' => 'in_progress']);
        $this->actingAs($support);
        foreach (['show', 'panel'] as $surface) {
            $html = $this->get(route('tickets.'.$surface, $ticket))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'aria-label="Registrar solución"'));
            $this->assertLessThan(strpos($html, 'aria-label="Registrar solución"'), strpos($html, 'name="comment"'));
            $document = new \DOMDocument();
            @$document->loadHTML($html);
            $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//section[@aria-label="Registrar solución"]/ancestor::form')->length);
            $this->assertSame(1, $xpath->query('//section[@aria-label="Registrar solución"]/form/textarea[@name="solution_text"]')->length);
        }
        foreach ([$owner, User::factory()->soporte()->create()] as $readOnlyUser) {
            $this->actingAs($readOnlyUser);
            foreach (['show', 'panel'] as $surface) {
                $this->get(route('tickets.'.$surface, $ticket))->assertOk()->assertDontSee('aria-label="Registrar solución"', false);
            }
        }
        $this->actingAs($support);
        foreach (['resolved', 'closed'] as $status) {
            $ticket->update(['status' => $status]);
            foreach (['show', 'panel'] as $surface) {
                $this->get(route('tickets.'.$surface, $ticket))->assertOk()->assertDontSee('aria-label="Registrar solución"', false);
            }
        }
    }
}
