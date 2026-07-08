<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketAttachment;
use App\Models\Department;
use App\Models\TicketHistory;
use App\Notifications\NewTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TicketController extends Controller
{
    // Extensiones de archivo permitidas
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'txt'];
    
    // Mime types permitidos
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/zip',
        'text/plain',
    ];

    public function index()
    {
        $user = Auth::user();
        
        if ($user->isSupport() || $user->isAdmin()) {
            $tickets = Ticket::with(['user', 'assignedTo', 'department'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
            $tickets = Ticket::where('user_id', $user->id)
                ->with(['user', 'assignedTo', 'department'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return view('tickets.index', compact('tickets'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        return view('tickets.create', compact('departments'));
    }

    public function store(Request $request)
    {
        // Rate limiting: máximo 10 tickets por hora
        $throttleKey = 'create_ticket:' . Auth::id();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return back()->withErrors(['general' => 'Ha excedido el límite de creación de tickets. Intente más tarde.']);
        }
        RateLimiter::hit($throttleKey, 3600);

        $request->validate([
            'title' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\.\,\-\(\)]*$/',
            'description' => 'required|string|max:10000',
            'category' => 'required|string|max:100',
            'device_type' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'department_id' => 'required|integer|exists:departments,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120',
        ]);

        // Generar número de ticket único
        $ticketNumber = 'TK-' . date('YmdHis') . rand(10000, 99999);

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'user_id' => Auth::id(),
            'department_id' => $request->department_id,
            'title' => htmlspecialchars($request->title, ENT_QUOTES, 'UTF-8'),
            'description' => htmlspecialchars($request->description, ENT_QUOTES, 'UTF-8'),
            'category' => htmlspecialchars($request->category, ENT_QUOTES, 'UTF-8'),
            'device_type' => htmlspecialchars($request->device_type, ENT_QUOTES, 'UTF-8'),
            'priority' => $request->priority,
            'status' => Ticket::STATUS_OPEN,
            'response_deadline_at' => Carbon::now()->addMinutes(30),
        ]);

        // Procesar adjuntos con validación estricta
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$this->isValidFile($file)) {
                    Log::warning('Intento de upload de archivo inválido', [
                        'user_id' => Auth::id(),
                        'filename' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ]);
                    continue;
                }

                $filename = $this->sanitizeFilename($file->getClientOriginalName());
                $path = $file->storeAs(
                    'tickets/' . $ticket->id,
                    $filename,
                    'public'
                );

                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'file_path' => $path,
                    'file_name' => $filename,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        // Notificar al equipo de soporte
        $supportTeam = \App\Models\User::where('role', 'support')
            ->where('department_id', $request->department_id)
            ->where('is_active', true)
            ->get();

        foreach ($supportTeam as $support) {
            $support->notify(new NewTicketNotification($ticket));
        }

        Log::info('Ticket creado: ' . $ticket->ticket_number, [
            'user_id' => Auth::id(),
            'department_id' => $request->department_id,
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket creado exitosamente: ' . $ticketNumber);
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        
        $ticket->load(['user', 'assignedTo', 'department', 'comments.user', 'attachments', 'history']);
        
        return view('tickets.show', compact('ticket'));
    }

    public function addComment(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        // Rate limiting: máximo 20 comentarios por hora
        $throttleKey = 'add_comment:' . Auth::id() . ':' . $ticket->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 20)) {
            return back()->withErrors(['comment' => 'Ha excedido el límite de comentarios. Intente más tarde.']);
        }
        RateLimiter::hit($throttleKey, 3600);

        $request->validate([
            'comment' => 'required|string|min:3|max:5000',
            'is_internal' => 'boolean',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|max:5120',
        ]);

        // Verificar permisos para comentarios internos
        if ($request->boolean('is_internal') && !Auth::user()->isAdmin() && !Auth::user()->isSupport()) {
            return back()->withErrors(['comment' => 'No tiene permiso para crear comentarios internos.']);
        }

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'comment' => htmlspecialchars($request->comment, ENT_QUOTES, 'UTF-8'),
            'ticket_status_at_comment' => $ticket->status,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        // Procesar adjuntos del comentario
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if (!$this->isValidFile($file)) {
                    Log::warning('Intento de upload de archivo inválido en comentario', [
                        'user_id' => Auth::id(),
                        'ticket_id' => $ticket->id,
                        'filename' => $file->getClientOriginalName(),
                    ]);
                    continue;
                }

                $filename = $this->sanitizeFilename($file->getClientOriginalName());
                $path = $file->storeAs(
                    'tickets/' . $ticket->id . '/comments',
                    $filename,
                    'public'
                );

                TicketAttachment::create([
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'file_path' => $path,
                    'file_name' => $filename,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        Log::info('Comentario agregado a ticket: ' . $ticket->ticket_number, [
            'user_id' => Auth::id(),
            'is_internal' => $request->boolean('is_internal'),
        ]);

        return back()->with('success', 'Comentario agregado.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'status' => 'required|in:open,in_progress,pending_user,forwarded,resolved,closed',
        ]);

        $oldStatus = $ticket->status;
        $newStatus = $request->status;

        $ticket->update(['status' => $newStatus]);

        // Registrar en historial
        \App\Models\TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'status_change',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'field_name' => 'status',
        ]);

        // Si se solicita información al usuario, establecer deadline
        if ($newStatus === Ticket::STATUS_PENDING_USER) {
            $ticket->update([
                'last_response_request_at' => Carbon::now(),
                'response_deadline_at' => Carbon::now()->addMinutes(30),
            ]);
        }

        return back()->with('success', 'Estado actualizado a: ' . $ticket->getStatusLabel());
    }

    public function assignTo(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $ticket->update(['assigned_to' => $request->user_id]);

        return back()->with('success', 'Ticket asignado.');
    }

    public function forward(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $request->validate([
            'department_id' => 'required|integer|exists:departments,id',
            'comment' => 'nullable|string|max:1000',
        ]);

        $oldDept = $ticket->department_id;
        $newDept = $request->department_id;

        $ticket->update([
            'department_id' => $newDept,
            'status' => Ticket::STATUS_FORWARDED,
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'forwarded',
            'old_value' => $oldDept,
            'new_value' => $newDept,
            'field_name' => 'department_id',
        ]);

        if ($request->comment) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'comment' => 'Ticket derivado: ' . htmlspecialchars($request->comment, ENT_QUOTES, 'UTF-8'),
                'is_internal' => true,
            ]);
        }

        Log::info('Ticket derivado: ' . $ticket->ticket_number, [
            'user_id' => Auth::id(),
            'from_department' => $oldDept,
            'to_department' => $newDept,
        ]);

        return back()->with('success', 'Ticket derivado.');
    }

    /**
     * Validar si un archivo es seguro
     */
    private function isValidFile($file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        return in_array($extension, self::ALLOWED_EXTENSIONS) && 
               in_array($mimeType, self::ALLOWED_MIME_TYPES);
    }

    /**
     * Sanitizar nombre de archivo
     */
    private function sanitizeFilename($filename): string
    {
        // Remover caracteres especiales y espacios
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        // Limitar a 255 caracteres
        $filename = substr($filename, 0, 255);
        // Asegurar que no esté vacío
        return $filename ?: 'archivo_'.time();
    }
}
