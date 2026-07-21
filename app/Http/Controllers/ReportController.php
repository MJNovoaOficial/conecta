<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Ticket;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['assignedTo', 'subcategoria.categoria', 'tipoIncidente', 'user'])
            ->orderBy('created_at', 'desc');

        // ── Filtros ──────────────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('agent_id')) {
            $query->where('assigned_to', $request->agent_id);
        }
        if ($request->filled('categoria_id')) {
            $query->whereHas('subcategoria', fn($q) => $q->where('categoria_id', $request->categoria_id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('ticket_number', 'like', '%' . $request->search . '%')
                  ->orWhere('title', 'like', '%' . $request->search . '%');
            });
        }
        // Filtro por solicitante (RN-25 / RF-AD-10)
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $tickets    = $query->paginate(50)->withQueryString();
        $agents     = User::where('role', 'support')->orWhere('role', 'admin')->orderBy('name')->get();
        $categorias = Categoria::orderBy('name')->get();
        $requesters = User::where('role', 'user')->orderBy('name')->get();

        // ── Resumen estadístico ───────────────────────────────────
        $baseQuery = clone $query->getQuery();
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
            'tickets', 'agents', 'categorias', 'requesters', 'summary',
            'avgResolution', 'slaCompliance', 'byAgent'
        ));
    }

    /**
     * Exportar reporte como CSV simple (compatible sin librerías externas).
     */
    public function export(Request $request)
    {
        $query = Ticket::with(['assignedTo', 'subcategoria.categoria', 'tipoIncidente', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('priority'))   $query->where('priority', $request->priority);
        if ($request->filled('agent_id'))   $query->where('assigned_to', $request->agent_id);
        if ($request->filled('user_id'))    $query->where('user_id', $request->user_id);
        if ($request->filled('date_from'))  $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))    $query->whereDate('created_at', '<=', $request->date_to);

        $tickets = $query->get();

        $filename = 'reporte_tickets_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            fputcsv($file, ['N° Ticket', 'Título', 'Solicitante', 'Estado', 'Prioridad',
                            'Categoría', 'Subcategoría', 'Tipo de Incidente',
                            'Técnico Asignado', 'Fecha Creación', 'Fecha Cierre'], ';');

            foreach ($tickets as $t) {
                fputcsv($file, [
                    $t->ticket_number,
                    $t->title,
                    $t->getCreatorName(),
                    $t->getStatusLabel(),
                    $t->getPriorityLabel(),
                    $t->subcategoria?->categoria?->name ?? $t->category ?? '',
                    $t->subcategoria?->name ?? '',
                    $t->tipoIncidente?->name ?? '',
                    $t->assignedTo?->name ?? 'Sin asignar',
                    $t->created_at->format('d/m/Y H:i'),
                    $t->closed_at?->format('d/m/Y H:i') ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar reporte como PDF usando DomPDF (RF-AD-11).
     */
    public function exportPdf(Request $request)
    {
        $query = Ticket::with(['assignedTo', 'subcategoria.categoria', 'tipoIncidente', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('priority'))  $query->where('priority', $request->priority);
        if ($request->filled('agent_id'))  $query->where('assigned_to', $request->agent_id);
        if ($request->filled('user_id'))   $query->where('user_id', $request->user_id);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

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

        $pdf = Pdf::loadView('admin.reports.pdf', compact('tickets', 'summary', 'slaCompliance', 'byAgent'))
                  ->setPaper('a4', 'landscape');

        return $pdf->download('reporte_tickets_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Exportar reporte como Excel XLSX usando PhpSpreadsheet (RF-AD-11).
     */
    public function exportExcel(Request $request)
    {
        $query = Ticket::with(['assignedTo', 'subcategoria.categoria', 'tipoIncidente', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('priority'))  $query->where('priority', $request->priority);
        if ($request->filled('agent_id'))  $query->where('assigned_to', $request->agent_id);
        if ($request->filled('user_id'))   $query->where('user_id', $request->user_id);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $tickets = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets');

        // Encabezados
        $headers = [
            'A1' => 'N° Ticket',
            'B1' => 'Título',
            'C1' => 'Solicitante',
            'D1' => 'Estado',
            'E1' => 'Prioridad',
            'F1' => 'Categoría',
            'G1' => 'Subcategoría',
            'H1' => 'Tipo de Incidente',
            'I1' => 'Técnico Asignado',
            'J1' => 'Fecha Creación',
            'K1' => 'Fecha Cierre',
            'L1' => 'SLA Respuesta',
            'M1' => 'SLA Resolución',
        ];

        // Estilo encabezados
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E3A5F']],
            'alignment' => ['horizontal' => 'center'],
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
            $sheet->getStyle($cell)->applyFromArray($headerStyle);
        }

        // Datos
        $row = 2;
        foreach ($tickets as $t) {
            $sheet->setCellValue("A{$row}", $t->ticket_number);
            $sheet->setCellValue("B{$row}", $t->title);
            $sheet->setCellValue("C{$row}", $t->getCreatorName());
            $sheet->setCellValue("D{$row}", $t->getStatusLabel());
            $sheet->setCellValue("E{$row}", $t->getPriorityLabel());
            $sheet->setCellValue("F{$row}", $t->subcategoria?->categoria?->name ?? '');
            $sheet->setCellValue("G{$row}", $t->subcategoria?->name ?? '');
            $sheet->setCellValue("H{$row}", $t->tipoIncidente?->name ?? '');
            $sheet->setCellValue("I{$row}", $t->assignedTo?->name ?? 'Sin asignar');
            $sheet->setCellValue("J{$row}", $t->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue("K{$row}", $t->closed_at?->format('d/m/Y H:i') ?? '');
            $sheet->setCellValue("L{$row}", $t->sla_response_deadline_at?->format('d/m/Y H:i') ?? '');
            $sheet->setCellValue("M{$row}", $t->sla_resolution_deadline_at?->format('d/m/Y H:i') ?? '');

            // Colorear filas alternadas
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFF0F4F8']],
                ]);
            }
            $row++;
        }

        // Ajustar anchos automáticamente
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Escribir a buffer y descargar
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'reporte_tickets_' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
