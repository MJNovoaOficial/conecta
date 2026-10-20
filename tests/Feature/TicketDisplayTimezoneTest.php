<?php

namespace Tests\Feature;

use App\Jobs\CloseResolvedTicketsJob;
use App\Mail\GuestTicketCreatedMail;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Support\DisplayDate;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class TicketDisplayTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        $this->travelTo(Carbon::parse('2026-10-20 14:05:30', 'UTC'));
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'ticket_number' => 'TZ-'.fake()->unique()->numerify('##########'),
            'user_id' => User::factory()->create()->id,
            'assigned_to' => User::factory()->soporte()->create()->id,
            'title' => 'Consulta de hora', 'description' => 'Conservar la información existente.',
            'status' => Ticket::STATUS_PENDING_USER, 'priority' => 'medium',
            'created_at' => '2026-10-20 13:15:00', 'updated_at' => '2026-10-20 13:50:00',
            'response_deadline_at' => '2026-10-20 15:05:00',
            'sla_resolution_deadline_at' => '2026-10-20 16:05:00',
        ], $attributes));
    }

    private function comment(Ticket $ticket): TicketComment
    {
        $comment = new TicketComment([
            'ticket_id' => $ticket->id, 'user_id' => $ticket->assigned_to,
            'comment' => 'Respuesta que debe verse a las 10:39.',
        ]);
        $comment->created_at = '2026-10-20 13:39:00';
        $comment->save();

        return $comment;
    }

    public function test_detalle_panel_listado_y_plazos_muestran_chile_sin_cambiar_datos(): void
    {
        $ticket = $this->ticket();
        $comment = $this->comment($ticket);
        $history = new TicketHistory(['ticket_id' => $ticket->id, 'user_id' => $ticket->assigned_to,
            'action' => 'comment_added', 'new_value' => 'Respuesta pública']);
        $history->created_at = '2026-10-20 13:39:00';
        $history->save();
        $before = $ticket->fresh()->getAttributes();
        $commentBefore = $comment->fresh()->getAttributes();
        $sla = $ticket->getSlaResolutionStatus();
        foreach ([$ticket->user, $ticket->assignedTo, User::factory()->administrador()->create()] as $user) {
            $this->actingAs($user)->get(route('tickets.show', $ticket))->assertOk()
                ->assertSee('20/10/2026 10:15')->assertSee('20/10/2026 10:39')
                ->assertSee('20/10/2026 10:50')->assertSee('20/10/2026 12:05')
                ->assertSee('20/10/2026 13:05')->assertDontSee('20/10/2026 13:39');
            $this->get(route('tickets.panel', $ticket))->assertOk()
                ->assertSee('20/10/2026 10:15')->assertSee('20/10/2026 10:39')
                ->assertDontSee('20/10/2026 13:39');
            $this->get(route('tickets.index'))->assertOk()->assertSee('20/10/2026 (10:50)');
        }
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->assertSame($commentBefore, $comment->fresh()->getAttributes());
        $this->assertSame($sla, $ticket->fresh()->getSlaResolutionStatus());
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('14:05:30', now()->format('H:i:s'));
    }

    public function test_el_invitado_ve_su_conversacion_en_hora_local_y_conserva_su_enlace(): void
    {
        $ticket = $this->ticket(['user_id' => null, 'guest_name' => 'Invitado de prueba',
            'guest_email' => 'guest@example.test', 'guest_token' => str_repeat('t', 40)]);
        $this->comment($ticket);
        $before = $ticket->fresh()->getAttributes();
        $this->get(route('tickets.guest.show', $ticket->guest_token))->assertOk()
            ->assertSee('20/10/2026 10:39')->assertSee('20/10/2026 10:15')
            ->assertSee('20/10/2026 12:05')->assertDontSee('20/10/2026 13:39');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
    }

    public function test_los_correos_de_creacion_muestran_la_misma_hora_local_sin_cambiar_destinatarios(): void
    {
        $ticket = $this->ticket(['guest_name' => 'Invitado', 'guest_token' => str_repeat('m', 40)]);
        $before = $ticket->fresh()->getAttributes();
        $mail = (new TicketCreatedNotification($ticket))->toMail($ticket->user);
        $html = view($mail->view, $mail->viewData)->render();
        $this->assertStringContainsString('20/10/2026 10:15', $html);
        $this->assertStringContainsString('20/10/2026 13:05', $html);
        $this->assertStringContainsString(route('tickets.show', $ticket), $html);
        $guestHtml = (new GuestTicketCreatedMail($ticket))->render();
        $this->assertStringContainsString('20/10/2026 10:15', $guestHtml);
        $this->assertStringContainsString(route('tickets.guest.show', $ticket->guest_token), $guestHtml);
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        Mail::assertNothingSent();
    }

    public function test_convertir_la_hora_no_adelanta_ni_atrasa_el_cierre_de_una_hora(): void
    {
        $ticket = $this->ticket(['status' => Ticket::STATUS_RESOLVED,
            'resolved_at' => '2026-10-20 13:39:00', 'solution_text' => 'Solución registrada correctamente.']);
        TicketHistory::create(['ticket_id' => $ticket->id, 'user_id' => $ticket->assigned_to,
            'action' => 'solution_registered', 'new_value' => Ticket::STATUS_RESOLVED]);
        $before = $ticket->fresh()->getAttributes();
        $this->actingAs($ticket->user)->get(route('tickets.show', $ticket))->assertOk()
            ->assertSee('Cierre automático a partir del 20/10/2026 11:39');
        $this->assertSame($before, $ticket->fresh()->getAttributes());
        $this->travelTo(Carbon::parse('2026-10-20 14:38:59', 'UTC'));
        (new CloseResolvedTicketsJob)->handle();
        $this->assertSame(Ticket::STATUS_RESOLVED, $ticket->fresh()->status);
        $this->travelTo(Carbon::parse('2026-10-20 14:39:00', 'UTC'));
        (new CloseResolvedTicketsJob)->handle();
        $ticket->refresh();
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->status);
        $this->assertSame('2026-10-20 14:39:00', $ticket->getRawOriginal('closed_at'));
        $this->assertSame('20/10/2026 11:39', DisplayDate::format($ticket->closed_at));
    }

    public function test_reportes_y_filtros_incluyen_solo_el_dia_que_ve_el_usuario_en_chile(): void
    {
        $admin = User::factory()->administrador()->create();
        $beforeDay = $this->ticket(['created_at' => '2026-10-20 02:59:59']);
        $first = $this->ticket(['created_at' => '2026-10-20 03:00:00',
            'closed_at' => '2026-10-20 13:39:00']);
        $last = $this->ticket(['created_at' => '2026-10-21 02:59:59']);
        $afterDay = $this->ticket(['created_at' => '2026-10-21 03:00:00']);
        $expected = [$first->id, $last->id];
        $before = Ticket::orderBy('id')->get()->map->getAttributes()->all();
        $filter = ['date_from' => '2026-10-20', 'date_to' => '2026-10-20'];
        foreach (['tickets.index', 'admin.reports.index'] as $route) {
            $this->actingAs($admin)->get(route($route, $filter))->assertOk()
                ->assertViewHas('tickets', fn ($tickets) => $tickets->pluck('id')->sort()->values()->all() === $expected)
                ->assertDontSee($beforeDay->ticket_number)->assertDontSee($afterDay->ticket_number);
        }
        // Both one-sided filters keep their original meaning, in local time.
        $this->get(route('admin.reports.index', ['date_from' => '2026-10-20']))->assertOk()
            ->assertViewHas('tickets', fn ($tickets) => $tickets->count() === 3 && !$tickets->contains('id', $beforeDay->id));
        $this->get(route('tickets.index', ['date_to' => '2026-10-20']))->assertOk()
            ->assertViewHas('tickets', fn ($tickets) => $tickets->count() === 3 && !$tickets->contains('id', $afterDay->id));

        $csv = $this->get(route('admin.reports.export', $filter))->assertOk();
        $path = $csv->baseResponse->getFile()->getPathname();
        try {
            $rows = array_map(fn ($line) => str_getcsv($line, ';'), file($path, FILE_IGNORE_NEW_LINES));
            $this->assertCount(3, $rows);
            $this->assertSame([$last->ticket_number, $first->ticket_number], array_column(array_slice($rows, 1), 0));
            $this->assertSame('20/10/2026 00:00', $rows[2][9]);
            $this->assertSame('20/10/2026 10:39', $rows[2][10]);
        } finally {
            @unlink($path);
        }
        $excel = $this->get(route('admin.reports.exportExcel', $filter))->assertOk();
        $path = $excel->baseResponse->getFile()->getPathname();
        $workbook = null;
        try {
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getActiveSheet();
            $this->assertSame('20/10/2026 00:00', $sheet->getCell('J3')->getValue());
            $this->assertSame('20/10/2026 10:39', $sheet->getCell('K3')->getValue());
            $this->assertSame('20/10/2026 13:05', $sheet->getCell('M3')->getValue());
        } finally {
            $workbook?->disconnectWorksheets();
            @unlink($path);
        }
        $pdfIds = null;
        View::composer('admin.reports.pdf', function ($view) use (&$pdfIds) {
            $pdfIds = $view->getData()['tickets']->pluck('id')->sort()->values()->all();
        });
        $pdf = $this->get(route('admin.reports.exportPdf', $filter))->assertOk();
        $path = $pdf->baseResponse->getFile()->getPathname();
        try {
            $this->assertSame('%PDF-', file_get_contents($path, false, null, 0, 5));
            $this->assertSame($expected, $pdfIds);
        } finally {
            @unlink($path);
        }
        $this->assertSame($before, Ticket::orderBy('id')->get()->map->getAttributes()->all());
    }

    public function test_configuracion_informa_hora_local_y_deja_utc_como_reloj_interno(): void
    {
        $this->actingAs(User::factory()->administrador()->create())->get(route('admin.settings.index'))
            ->assertOk()->assertSee('America/Santiago — 20/10/2026 11:05:30 (fechas internas: UTC)');
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('UTC', date_default_timezone_get());
        $this->assertDatabaseCount('system_settings', 0);
    }
}
