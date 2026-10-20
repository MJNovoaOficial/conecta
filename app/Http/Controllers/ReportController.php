<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Ticket;
use App\Models\User;
use App\Support\DisplayDate;
use App\Support\FilterValidation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredTickets($request);

        $tickets    = $query->paginate(50)->withQueryString();
        $agents     = User::where('role', 'support')->orWhere('role', 'admin')->orderBy('name')->get();
        $categorias = Categoria::orderBy('name')->get();
        $requesters = User::where('role', 'user')->orderBy('name')->get();
        $departments = \App\Models\Department::where('is_active', true)->orderBy('name')->get();

        // ── Resumen estadístico ───────────────────────────────────
        $summary = [
            'total'         => Ticket::count(),
            'open'          => Ticket::where('status', 'open')->count(),
            'in_progress'   => Ticket::where('status', 'in_progress')->count(),
            'pending_user'  => Ticket::where('status', 'pending_user')->count(),
            'resolved'      => Ticket::where('status', 'resolved')->count(),
            'closed'        => Ticket::where('status', 'closed')->count(),
        ];

        // Tiempo promedio de resolución (en horas)
        $avgResolution = Ticket::whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
            ->value('avg_hours');

        // SLA compliance: % de tickets resueltos dentro del SLA
        $resolvedWithSla = Ticket::whereNotNull('resolved_at')
            ->whereNotNull('sla_resolution_deadline_at')
            ->where('resolved_at', '<=', DB::raw('sla_resolution_deadline_at'))
            ->count();
        $totalResolved = Ticket::whereNotNull('resolved_at')->count();
        $slaCompliance = $totalResolved > 0 ? round(($resolvedWithSla / $totalResolved) * 100, 1) : null;

        // Tickets por técnico
        $byAgent = User::where(function($q) {
            $q->where('role', 'support')->orWhere('role', 'admin');
        })
        ->withCount(['assignedTickets as total_tickets',
            'assignedTickets as open_tickets' => fn($q) => $q->where('status', 'open'),
            'assignedTickets as in_progress_tickets' => fn($q) => $q->where('status', 'in_progress'),
            'assignedTickets as resolved_tickets' => fn($q) => $q->whereIn('status', ['resolved', 'closed']),
        ])
        ->orderByDesc('total_tickets')
        ->get();

        return view('admin.reports.index', compact(
            'tickets', 'agents', 'categorias', 'requesters', 'departments', 'summary',
            'avgResolution', 'slaCompliance', 'byAgent'
        ));
    }

    /**
     * Exportar reporte como CSV — usa archivo temporal para máxima compatibilidad de navegador.
     */
    public function export(Request $request)
    {
        $query = $this->filteredTickets($request);

        $tickets  = $query->get();
        $filename = 'reporte_tickets_' . DisplayDate::format(now(), 'Y-m-d') . '.csv';
        $tmpPath  = tempnam(sys_get_temp_dir(), 'conecta_csv_');

        // Escribir CSV al disco temporal
        $file = fopen($tmpPath, 'w');
        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel
        fputcsv($file, [
            'N° Ticket','Título','Solicitante','Estado','Prioridad',
            'Categoría','Subcategoría','Tipo de Incidente',
            'Técnico Asignado','Fecha Creación','Fecha Cierre',
        ], ';');
        foreach ($tickets as $t) {
            fputcsv($file, [
                $t->ticket_number,
                $t->title,
                $t->getCreatorName(),
                $t->getStatusLabel(),
                $t->getPriorityLabel(),
                $t->subcategoria?->categoria?->name ?? '',
                $t->subcategoria?->name ?? '',
                $t->tipoIncidente?->name ?? '',
                $t->assignedTo?->name ?? 'Sin asignar',
                DisplayDate::format($t->created_at),
                DisplayDate::format($t->closed_at, empty: ''),
            ], ';');
        }
        fclose($file);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Exportar reporte como PDF usando DomPDF (RF-AD-11).
     */
    public function exportPdf(Request $request)
    {
        $query = $this->filteredTickets($request);

        $tickets = $query->take(500)->get(); // Límite para evitar PDFs enormes

        $summary = [
            'total'        => Ticket::count(),
            'open'         => Ticket::where('status', 'open')->count(),
            'in_progress'  => Ticket::where('status', 'in_progress')->count(),
            'pending_user' => Ticket::where('status', 'pending_user')->count(),
            'resolved'     => Ticket::where('status', 'resolved')->count(),
            'closed'       => Ticket::where('status', 'closed')->count(),
        ];

        $resolvedWithSla = Ticket::whereNotNull('resolved_at')
            ->whereNotNull('sla_resolution_deadline_at')
            ->where('resolved_at', '<=', DB::raw('sla_resolution_deadline_at'))
            ->count();
        $totalResolved   = Ticket::whereNotNull('resolved_at')->count();
        $slaCompliance   = $totalResolved > 0 ? round(($resolvedWithSla / $totalResolved) * 100, 1) : null;

        $byAgent = User::where(function($q) {
            $q->where('role', 'support')->orWhere('role', 'admin');
        })->withCount([
            'assignedTickets as total_tickets',
            'assignedTickets as open_tickets'       => fn($q) => $q->where('status', 'open'),
            'assignedTickets as in_progress_tickets' => fn($q) => $q->where('status', 'in_progress'),
            'assignedTickets as resolved_tickets'   => fn($q) => $q->whereIn('status', ['resolved', 'closed']),
        ])->orderByDesc('total_tickets')->get();

        $pdf      = Pdf::loadView('admin.reports.pdf', compact('tickets', 'summary', 'slaCompliance', 'byAgent'))
                       ->setPaper('a4', 'landscape');
        $filename = 'reporte_tickets_' . DisplayDate::format(now(), 'Y-m-d') . '.pdf';
        $tmpPath  = tempnam(sys_get_temp_dir(), 'conecta_pdf_') . '.pdf';
        $pdf->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Exportar reporte como Excel XLSX usando PhpSpreadsheet (RF-AD-11).
     * Fix encoding: se usa setCellValueExplicit con TYPE_STRING para acentos/eñes.
     */
    public function exportExcel(Request $request)
    {
        $query = $this->filteredTickets($request);

        $tickets = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets');

        // Fix encoding: usar TYPE_STRING explícito para cabeceras con tildes/eñes
        $dt = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

        $headers = [
            'A' => 'N° Ticket',
            'B' => 'Título',
            'C' => 'Solicitante',
            'D' => 'Estado',
            'E' => 'Prioridad',
            'F' => 'Categoría',
            'G' => 'Subcategoría',
            'H' => 'Tipo de Incidente',
            'I' => 'Técnico Asignado',
            'J' => 'Fecha Creación',
            'K' => 'Fecha Cierre',
            'L' => 'SLA Respuesta',
            'M' => 'SLA Resolución',
        ];

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ];

        // Usar getCell($col.$row) en lugar del deprecado getCellByColumnAndRow()
        foreach ($headers as $col => $label) {
            $cell = $col . '1';
            $sheet->getCell($cell)->setValueExplicit($label, $dt);
            $sheet->getStyle($cell)->applyFromArray($headerStyle);
        }
        $sheet->getRowDimension(1)->setRowHeight(18);

        // Datos
        $row = 2;
        foreach ($tickets as $t) {
            $rowData = [
                'A' => $t->ticket_number,
                'B' => $t->title,
                'C' => $t->getCreatorName(),
                'D' => $t->getStatusLabel(),
                'E' => $t->getPriorityLabel(),
                'F' => $t->subcategoria?->categoria?->name ?? '',
                'G' => $t->subcategoria?->name ?? '',
                'H' => $t->tipoIncidente?->name ?? '',
                'I' => $t->assignedTo?->name ?? 'Sin asignar',
                'J' => DisplayDate::format($t->created_at),
                'K' => DisplayDate::format($t->closed_at, empty: ''),
                'L' => DisplayDate::format($t->sla_response_deadline_at, empty: ''),
                'M' => DisplayDate::format($t->sla_resolution_deadline_at, empty: ''),
            ];

            foreach ($rowData as $col => $val) {
                $sheet->getCell($col . $row)->setValueExplicit((string) $val, $dt);
            }

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFF0F4F8']],
                ]);
            }
            $row++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'reporte_tickets_' . DisplayDate::format(now(), 'Y-m-d') . '.xlsx';
        $tmpPath  = tempnam(sys_get_temp_dir(), 'conecta_xlsx_') . '.xlsx';
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** El listado y las descargas deben seleccionar exactamente los mismos casos. */
    private function filteredTickets(Request $request): Builder
    {
        // Validar antes de concatenar textos o pasar fechas/arrays al motor SQL.
        // Los ID inexistentes siguen devolviendo cero resultados, sin cambiar datos.
        FilterValidation::validate($request, [
            'search'        => ['nullable', 'string'],
            'status'        => ['nullable', 'string'],
            'priority'      => ['nullable', 'string'],
            'agent_id'      => ['nullable', 'integer', 'min:1'],
            'categoria_id'  => ['nullable', 'integer', 'min:1'],
            'user_id'       => ['nullable', 'integer', 'min:1'],
            'department_id' => ['nullable', 'integer', 'min:1'],
            'date_from'     => ['nullable', 'date_format:Y-m-d'],
            'date_to'       => ['nullable', 'date_format:Y-m-d'],
            'page'          => ['nullable', 'integer', 'min:1'],
        ], 'admin.reports.index');

        $query = Ticket::with(['assignedTo', 'subcategoria.categoria', 'tipoIncidente', 'user'])
            ->orderBy('created_at', 'desc');

        foreach (['status' => 'status', 'priority' => 'priority', 'agent_id' => 'assigned_to',
            'user_id' => 'user_id', 'department_id' => 'department_id'] as $filter => $column) {
            if ($request->filled($filter)) {
                $query->where($column, $request->input($filter));
            }
        }
        if ($request->filled('categoria_id')) {
            $query->whereHas('subcategoria', fn ($q) => $q->where('categoria_id', $request->input('categoria_id')));
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', DisplayDate::dayStartUtc($request->input('date_from')));
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<', DisplayDate::nextDayStartUtc($request->input('date_to')));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ticket_number', 'like', '%' . $request->input('search') . '%')
                    ->orWhere('title', 'like', '%' . $request->input('search') . '%');
            });
        }

        return $query;
    }

    /**
     * Reporte de KPIs por agente de soporte (RF-AD-11 extensión).
     */
    public function agentReport()
    {
        $priorityWeight = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];

        $agents = User::whereIn('role', ['support', 'admin'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (User $agent) use ($priorityWeight) {
                $tickets = \App\Models\Ticket::where('assigned_to', $agent->id)->get();

                $attended  = $tickets->count();
                $resolved  = $tickets->whereNotNull('resolved_at');

                // Tiempo promedio de resolución (horas)
                $avgResolution = $resolved->count() > 0
                    ? round($resolved->avg(fn($t) => $t->created_at->diffInMinutes($t->resolved_at)) / 60, 1)
                    : null;

                // Tiempo promedio de primera respuesta (primer comentario del agente)
                $firstResponseTimes = \App\Models\TicketComment::where('user_id', $agent->id)
                    ->whereIn('ticket_id', $tickets->pluck('id'))
                    ->selectRaw('ticket_id, MIN(created_at) as first_response')
                    ->groupBy('ticket_id')
                    ->get();

                $avgFirstResponse = null;
                if ($firstResponseTimes->count() > 0) {
                    $times = $firstResponseTimes->map(function ($fr) {
                        $ticket = \App\Models\Ticket::find($fr->ticket_id);
                        return $ticket ? $ticket->created_at->diffInMinutes($fr->first_response) : null;
                    })->filter()->values();

                    $avgFirstResponse = $times->count() > 0
                        ? round($times->avg() / 60, 1)
                        : null;
                }

                // Ticket más tardado en resolver
                $slowestTicket = $resolved->sortByDesc(fn($t) =>
                    $t->created_at->diffInMinutes($t->resolved_at)
                )->first();

                // Puntaje de complejidad promedio (peso por prioridad)
                $complexityScore = $attended > 0
                    ? round($tickets->avg(fn($t) => $priorityWeight[$t->priority] ?? 2), 2)
                    : null;

                // Satisfacción de las personas atendidas. Se cuenta por
                // agente_id de la encuesta y no por los tickets asignados hoy:
                // la calificación queda con quien atendía cuando se respondió.
                $encuestas         = \App\Models\EncuestaSatisfaccion::where('agente_id', $agent->id);
                $satisfactionCount = (clone $encuestas)->count();
                $satisfactionAvg   = $satisfactionCount > 0
                    ? round((float) (clone $encuestas)->avg('calificacion'), 1)
                    : null;

                return [
                    'agent'              => $agent,
                    'attended'           => $attended,
                    'resolved'           => $resolved->count(),
                    'open_active'        => $tickets->whereIn('status', ['open','in_progress','pending_user'])->count(),
                    'avg_resolution_h'   => $avgResolution,
                    'avg_first_resp_h'   => $avgFirstResponse,
                    'slowest_ticket'     => $slowestTicket,
                    'slowest_hours'      => $slowestTicket
                        ? round($slowestTicket->created_at->diffInMinutes($slowestTicket->resolved_at) / 60, 1)
                        : null,
                    'complexity_score'   => $complexityScore,
                    'satisfaction_avg'   => $satisfactionAvg,
                    'satisfaction_count' => $satisfactionCount,
                ];
            });

        return view('admin.reports.agents', compact('agents'));
    }
}
