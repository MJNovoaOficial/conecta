<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketAttachment;
use App\Models\Department;
use App\Models\TicketHistory;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $statusFilter = request('status');

        if ($user->isSupport() || $user->isAdmin()) {
            $query = Ticket::with(['user', 'assignedTo', 'department']);
            $countQuery = Ticket::query();
        } else {
            $query = Ticket::where('user_id', $user->id)->with(['user', 'assignedTo', 'department']);
            $countQuery = Ticket::where('user_id', $user->id);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $counts = [
            'total'        => (clone $countQuery)->count(),
            'open'         => (clone $countQuery)->where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress'  => (clone $countQuery)->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'pending_user' => (clone $countQuery)->where('status', Ticket::STATUS_PENDING_USER)->count(),
            'forwarded'    => (clone $countQuery)->where('status', Ticket::STATUS_FORWARDED)->count(),
            'resolved'     => (clone $countQuery)->where('status', Ticket::STATUS_RESOLVED)->count(),
            'closed'       => (clone $countQuery)->where('status', Ticket::STATUS_CLOSED)->count(),
        ];

        $departments = Department::where('is_active', true)->get();

        return view('tickets.index', compact('tickets', 'counts', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $recentTickets = Ticket::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        return view('tickets.create', compact('departments', 'recentTickets'));
    }

    /**
     * Formulario de creación para invitados (sin autenticación)
     */
    public function guestCreate()
    {
        $departments = Department::where('is_active', true)->get();
        return view('tickets.guest_create', compact('departments'));
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
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'category' => 'required|string|max:100',
            'device_type' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'department_id' => 'required|integer|exists:departamentos,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120',
        ]);

        // Generar número de ticket único
        $ticketNumber = 'TK-' . date('YmdHis') . '-' . rand(1000, 9999);

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'user_id' => Auth::id(),
            'department_id' => $request->department_id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'device_type' => $request->device_type,
            'priority' => $request->priority,
            'status' => Ticket::STATUS_OPEN,
        ]);

        // Procesar adjuntos con validación estricta
        $this->processAttachments($request, $ticket);

        // Notificar al equipo de soporte
        $this->notifySupportTeam($ticket);

        Log::info('Ticket creado: ' . $ticket->ticket_number, [
            'user_id' => Auth::id(),
            'department_id' => $request->department_id,
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket creado exitosamente: ' . $ticketNumber);
    }

    /**
     * Crear ticket como invitado (sin autenticación)
     */
    public function guestStore(Request $request)
    {
        // Rate limiting por IP
        $throttleKey = 'guest_ticket:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['general' => 'Ha excedido el límite de creación de tickets. Intente más tarde.']);
        }
        RateLimiter::hit($throttleKey, 3600);

        $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_department' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'category' => 'required|string|max:100',
            'device_type' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'department_id' => 'required|integer|exists:departamentos,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120',
        ]);

        $ticketNumber = 'TK-' . date('YmdHis') . '-' . rand(1000, 9999);
        $guestToken = Str::random(40);

        $ticket = Ticket::create([
            'ticket_number' => $ticketNumber,
            'user_id' => null,
            'department_id' => $request->department_id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'device_type' => $request->device_type,
            'priority' => $request->priority,
            'status' => Ticket::STATUS_OPEN,
            'guest_name' => $request->guest_name,
            'guest_email' => $request->guest_email,
            'guest_department' => $request->guest_department,
            'guest_token' => $guestToken,
        ]);

        // Procesar adjuntos
        $this->processAttachments($request, $ticket, null);

        // Notificar al equipo de soporte
        $this->notifySupportTeam($ticket);

        Log::info('Ticket de invitado creado: ' . $ticket->ticket_number, [
            'guest_email' => $request->guest_email,
            'department_id' => $request->department_id,
        ]);

        return redirect()->route('tickets.guest.show', ['token' => $guestToken])
            ->with('success', 'Ticket creado exitosamente: ' . $ticketNumber . '. Guarda este enlace para dar seguimiento.');
    }

    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        
        $ticket->load(['user', 'assignedTo', 'department', 'comments.user', 'comments.attachments', 'attachments', 'history.user']);
        
        $departments = Department::where('is_active', true)->get();
        $supportUsers = User::whereIn('role', ['support', 'admin'])->where('is_active', true)->get();

        return view('tickets.show', compact('ticket', 'departments', 'supportUsers'));
    }

    /**
     * Vista compacta sin layout para el panel lateral (AJAX)
     */
    public function panel(Ticket $ticket)
    {
        $this->authorize('view', $ticket);
        $ticket->load(['user', 'assignedTo', 'department', 'comments.user', 'comments.attachments', 'attachments', 'history.user']);
        $departments  = Department::where('is_active', true)->get();
        $supportUsers = User::whereIn('role', ['support', 'admin'])->where('is_active', true)->get();
        return view('tickets.panel', compact('ticket', 'departments', 'supportUsers'));
    }

    /**
     * Vista de ticket para invitados (acceso por token)
     */
    public function guestShow(string $token)
    {
        $ticket = Ticket::where('guest_token', $token)
            ->with(['assignedTo', 'department', 'comments.user', 'comments.attachments', 'attachments', 'history.user'])
            ->firstOrFail();

        $departments = Department::where('is_active', true)->get();
        $supportUsers = User::whereIn('role', ['support', 'admin'])->where('is_active', true)->get();

        return view('tickets.show', compact('ticket', 'departments', 'supportUsers'));
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
            'comment' => $request->comment,
            'ticket_status_at_comment' => $ticket->status,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        // Procesar adjuntos del comentario
        $this->processAttachments($request, $ticket, $comment->id);

        // Si el ticket estaba en "pendiente_usuario" y el creador responde, registrar respuesta
        if ($ticket->status === Ticket::STATUS_PENDING_USER && Auth::id() === $ticket->user_id) {
            $ticket->update([
                'user_responded_at' => Carbon::now(),
                'status' => Ticket::STATUS_IN_PROGRESS,
            ]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'action' => 'user_responded',
                'old_value' => Ticket::STATUS_PENDING_USER,
                'new_value' => Ticket::STATUS_IN_PROGRESS,
                'field_name' => 'status',
            ]);
        }

        // Notificar a los involucrados
        $this->notifyTicketUpdate($ticket, 'Se agregó un nuevo comentario al ticket.');

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

        $updateData = ['status' => $newStatus];

        // Si se solicita información al usuario, establecer deadline
        if ($newStatus === Ticket::STATUS_PENDING_USER) {
            $updateData['last_response_request_at'] = Carbon::now();
            $updateData['response_deadline_at'] = Carbon::now()->addMinutes(30);
            $updateData['user_responded_at'] = null;
        }

        $ticket->update($updateData);

        // Registrar en historial
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'status_change',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'field_name' => 'status',
        ]);

        // Notificar cambio de estado
        $this->notifyTicketUpdate($ticket, 'El estado del ticket cambió de "' . $this->getStatusLabelStatic($oldStatus) . '" a "' . $ticket->getStatusLabel() . '".');

        return back()->with('success', 'Estado actualizado a: ' . $ticket->getStatusLabel());
    }

    public function assignTo(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = Auth::user();

        // Solo el admin o el agente asignado pueden reasignar
        if (!$user->isAdmin() && $ticket->assigned_to !== $user->id) {
            return back()->with('error', 'Solo el administrador o el agente asignado pueden reasignar este ticket.');
        }
        $request->validate([
            'user_id' => 'required|exists:usuarios,id',
        ]);

        $oldAssigned = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $request->user_id]);

        // Registrar en historial
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'assigned',
            'old_value' => $oldAssigned,
            'new_value' => $request->user_id,
            'field_name' => 'assigned_to',
        ]);

        // Notificar al usuario asignado
        $assignedUser = User::find($request->user_id);
        if ($assignedUser) {
            $assignedUser->notify(new TicketUpdatedNotification($ticket, 'Se te ha asignado el ticket ' . $ticket->ticket_number));
        }

        // Notificar al creador del ticket
        $this->notifyTicketUpdate($ticket, 'Tu ticket ha sido asignado a ' . ($assignedUser->name ?? 'un agente de soporte') . '.');

        return back()->with('success', 'Ticket asignado a ' . ($assignedUser->name ?? 'soporte') . '.');
    }

    /**
     * Asignarse el ticket a sí mismo
     */
    public function selfAssign(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = Auth::user();

        // Si ya está asignado a OTRO agente, solo el admin puede reasignarlo
        if ($ticket->assigned_to && $ticket->assigned_to !== $user->id) {
            if (!$user->isAdmin()) {
                $assignedName = optional($ticket->assignedTo)->name ?? 'otro agente';
                return back()->with('error', "Este ticket ya fue tomado por {$assignedName}. Solo un administrador puede reasignarlo.");
            }
        }

        $oldAssigned = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $user->id]);

        // Si está abierto, pasar a en proceso
        if ($ticket->status === Ticket::STATUS_OPEN) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $user->id,
                'action'    => 'status_change',
                'old_value' => Ticket::STATUS_OPEN,
                'new_value' => Ticket::STATUS_IN_PROGRESS,
                'field_name'=> 'status',
            ]);
        }

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'action'    => 'self_assigned',
            'old_value' => $oldAssigned,
            'new_value' => $user->id,
            'field_name'=> 'assigned_to',
        ]);

        // Notificar al creador
        $this->notifyTicketUpdate($ticket, 'Tu ticket ha sido tomado por ' . $user->name . '.');

        return back()->with('success', 'Te has asignado este ticket.');
    }

    public function forward(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = Auth::user();

        // Solo el admin o el agente asignado pueden derivar
        if (!$user->isAdmin() && $ticket->assigned_to !== $user->id) {
            return back()->with('error', 'Solo el administrador o el agente asignado pueden derivar este ticket.');
        }

        $request->validate([
            'department_id' => 'required|integer|exists:departamentos,id',
            'comment' => 'nullable|string|max:1000',
        ]);

        $oldDept = $ticket->department_id;
        $newDept = $request->department_id;
        $oldDeptName = $ticket->department->name ?? 'N/A';
        $newDeptName = Department::find($newDept)->name ?? 'N/A';

        $ticket->update([
            'department_id' => $newDept,
            'status' => Ticket::STATUS_FORWARDED,
            'assigned_to' => null, // Resetear asignación al derivar
        ]);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'action' => 'forwarded',
            'old_value' => $oldDeptName,
            'new_value' => $newDeptName,
            'field_name' => 'department_id',
        ]);

        if ($request->comment) {
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'comment' => 'Ticket derivado a ' . $newDeptName . ': ' . $request->comment,
                'ticket_status_at_comment' => Ticket::STATUS_FORWARDED,
                'is_internal' => true,
            ]);
        }

        // Notificar al nuevo equipo de soporte
        $newSupportTeam = User::where('role', 'support')
            ->where('department_id', $newDept)
            ->where('is_active', true)
            ->get();

        foreach ($newSupportTeam as $support) {
            $support->notify(new TicketUpdatedNotification($ticket, 'Se ha derivado un ticket a tu departamento: ' . $ticket->ticket_number));
        }

        // Notificar al creador
        $this->notifyTicketUpdate($ticket, 'Tu ticket ha sido derivado al departamento ' . $newDeptName . '.');

        Log::info('Ticket derivado: ' . $ticket->ticket_number, [
            'user_id' => Auth::id(),
            'from_department' => $oldDeptName,
            'to_department' => $newDeptName,
        ]);

        return back()->with('success', 'Ticket derivado a ' . $newDeptName . '.');
    }

    /**
     * Notificar a todo el equipo de soporte sobre un nuevo ticket
     */
    private function notifySupportTeam(Ticket $ticket)
    {
        // Notificar a todos los agentes de soporte y admins activos (sin filtro de departamento)
        $supportTeam = User::whereIn('role', ['support', 'admin'])
            ->where('is_active', true)
            ->get();

        foreach ($supportTeam as $support) {
            $support->notify(new NewTicketNotification($ticket));
        }
    }

    /**
     * Notificar actualización del ticket a los involucrados
     */
    private function notifyTicketUpdate(Ticket $ticket, string $message)
    {
        $notified = collect();

        // Notificar al creador (si es usuario registrado y no es quien hace la acción)
        if ($ticket->user_id && $ticket->user_id !== Auth::id()) {
            $ticket->user->notify(new TicketUpdatedNotification($ticket, $message));
            $notified->push($ticket->user_id);
        }

        // Notificar al asignado (si no es quien hace la acción y no fue ya notificado)
        if ($ticket->assigned_to && $ticket->assigned_to !== Auth::id() && !$notified->contains($ticket->assigned_to)) {
            $ticket->assignedTo->notify(new TicketUpdatedNotification($ticket, $message));
        }
    }

    /**
     * Procesar archivos adjuntos
     */
    private function processAttachments(Request $request, Ticket $ticket, ?int $commentId = null)
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

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
            $subfolder = $commentId ? 'tickets/' . $ticket->id . '/comments' : 'tickets/' . $ticket->id;
            $path = $file->storeAs($subfolder, $filename, 'public');

            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'comment_id' => $commentId,
                'file_path' => $path,
                'file_name' => $filename,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);
        }
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

    /**
     * Obtener label de estado estáticamente
     */
    private function getStatusLabelStatic(string $status): string
    {
        return match($status) {
            'open' => 'Abierto',
            'in_progress' => 'En Proceso',
            'pending_user' => 'Pendiente Usuario',
            'forwarded' => 'Derivado',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            default => 'Desconocido',
        };
    }
}
