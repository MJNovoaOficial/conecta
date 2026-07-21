@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('styles')
<style>
/* ── Layout ────────────────────────────────────────────── */
.ticket-page { display:flex; gap:24px; align-items:flex-start; }
.ticket-main { flex:1; min-width:0; }
.ticket-side { width:280px; flex-shrink:0; }

/* ── Header card ───────────────────────────────────────── */
.tk-header {
    background: linear-gradient(135deg,#1a2332 0%,#243447 100%);
    border-radius: 12px;
    padding: 24px 26px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.tk-header::before {
    content:'';
    position:absolute;
    top:-40px; right:-40px;
    width:160px; height:160px;
    border-radius:50%;
    background:rgba(52,152,219,.12);
}
.tk-num  { font-size:.72rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#90cdf4; margin-bottom:6px; }
.tk-title{ font-size:1.2rem; font-weight:700; color:#fff; line-height:1.35; margin-bottom:10px; }
.tk-meta { font-size:.78rem; color:#90a4ae; }
.tk-meta strong { color:#b0bec5; }

/* ── Badges ─────────────────────────────────────────────── */
.tk-badges { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
.tk-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 12px; border-radius:20px; font-size:.72rem; font-weight:700;
}
.tk-badge-open    { background:#d1fae5; color:#065f46; }
.tk-badge-inprog  { background:#fef3c7; color:#92400e; }
.tk-badge-pending { background:#ffedd5; color:#9a3412; }
.tk-badge-fwd     { background:#dbeafe; color:#1e40af; }
.tk-badge-resolved{ background:#ede9fe; color:#5b21b6; }
.tk-badge-closed  { background:rgba(255,255,255,.1); color:#90cdf4; }
.tk-badge-low     { background:#f0fdf4; color:#166534; }
.tk-badge-medium  { background:#fefce8; color:#854d0e; }
.tk-badge-high    { background:#fff7ed; color:#9a3412; }
.tk-badge-critical{ background:#fef2f2; color:#991b1b; }

/* ── Cards ──────────────────────────────────────────────── */
.tk-card {
    background:#fff;
    border:1px solid #e8ecf0;
    border-radius:10px;
    margin-bottom:18px;
    overflow:hidden;
}
.tk-card-header {
    padding:13px 18px;
    background:#f7f9fc;
    border-bottom:1px solid #e8ecf0;
    font-size:.8rem;
    font-weight:700;
    color:#4a5568;
    display:flex;
    align-items:center;
    gap:8px;
}
.tk-card-body { padding:18px; }

/* ── Description ────────────────────────────────────────── */
.tk-desc {
    font-size:.875rem;
    line-height:1.7;
    color:#2d3748;
    white-space:pre-wrap;
}

/* ── Comments ───────────────────────────────────────────── */
.comment-item {
    border-left:3px solid #e2e8f0;
    border-radius:0 8px 8px 0;
    padding:14px 16px;
    margin-bottom:12px;
    background:#fafbfc;
    transition:box-shadow .15s;
}
.comment-item:hover { box-shadow:0 2px 8px rgba(0,0,0,.06); }
.comment-item.internal { border-left-color:#f59e0b; background:#fffbeb; }
.comment-head { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.c-avatar {
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.75rem; font-weight:700; color:#fff; flex-shrink:0;
}
.av-u { background:linear-gradient(135deg,#3498db,#2980b9); }
.av-s { background:linear-gradient(135deg,#27ae60,#2ecc71); }
.av-a { background:linear-gradient(135deg,#e74c3c,#c0392b); }
.c-name { font-size:.82rem; font-weight:700; color:#1a2332; }
.c-role { font-size:.72rem; color:#a0aec0; margin-left:4px; }
.c-time { font-size:.72rem; color:#a0aec0; margin-left:auto; white-space:nowrap; }
.c-body { font-size:.84rem; color:#4a5568; line-height:1.6; white-space:pre-wrap; }

/* ── Reply form ─────────────────────────────────────────── */
.reply-wrap { background:#f7f9fc; border:1px solid #e8ecf0; border-radius:8px; padding:16px; }
.reply-wrap textarea {
    width:100%; border:1.5px solid #e2e8f0; border-radius:7px;
    padding:10px 13px; font-size:.84rem; resize:vertical; min-height:90px;
    outline:none; font-family:inherit; color:#2d3748;
    transition:border-color .15s;
}
.reply-wrap textarea:focus { border-color:#3498db; box-shadow:0 0 0 3px rgba(52,152,219,.1); }
.reply-footer { display:flex; align-items:center; justify-content:space-between; margin-top:10px; gap:10px; flex-wrap:wrap; }
.btn-send {
    background:linear-gradient(135deg,#1a2332,#2d3748);
    color:#fff; border:none; padding:8px 20px; border-radius:7px;
    font-size:.82rem; font-weight:600; cursor:pointer; transition:all .2s;
    display:inline-flex; align-items:center; gap:6px;
}
.btn-send:hover { background:linear-gradient(135deg,#2d3748,#4a5568); }

/* ── Side actions ───────────────────────────────────────── */
.side-card {
    background:#fff;
    border:1px solid #e8ecf0;
    border-radius:10px;
    margin-bottom:14px;
    overflow:hidden;
}
.side-card-header {
    padding:11px 16px;
    background:#f7f9fc;
    border-bottom:1px solid #e8ecf0;
    font-size:.75rem;
    font-weight:700;
    color:#718096;
    text-transform:uppercase;
    letter-spacing:.06em;
}
.side-card-body { padding:14px 16px; }
.side-status-select {
    width:100%; padding:7px 10px; border:1.5px solid #e2e8f0;
    border-radius:7px; font-size:.82rem; cursor:pointer; color:#2d3748;
    background:#fff; outline:none;
}
.side-status-select:focus { border-color:#3498db; }
.side-btn {
    display:flex; align-items:center; justify-content:center; gap:6px;
    width:100%; padding:8px; border-radius:7px; font-size:.8rem;
    font-weight:600; cursor:pointer; border:none; margin-bottom:7px;
    transition:all .15s; text-decoration:none;
}
.side-btn:last-child { margin-bottom:0; }
.side-btn-green { background:#27ae60; color:#fff; }
.side-btn-green:hover { background:#1e8449; }
.side-btn-outline { background:#fff; color:#4a5568; border:1px solid #e2e8f0; }
.side-btn-outline:hover { background:#f7f9fc; color:#2d3748; }
.lock-note {
    display:flex; align-items:center; gap:7px; padding:8px 12px;
    border-radius:7px; background:#fffbeb; border:1px solid #fde68a;
    color:#92400e; font-size:.78rem; font-weight:600; margin-bottom:7px;
}
.mine-note {
    display:flex; align-items:center; gap:7px; padding:8px 12px;
    border-radius:7px; background:#d1fae5; border:1px solid #6ee7b7;
    color:#065f46; font-size:.78rem; font-weight:600; margin-bottom:7px;
}

/* ── Info list ──────────────────────────────────────────── */
.info-list { list-style:none; padding:0; margin:0; }
.info-list li {
    display:flex; justify-content:space-between; align-items:baseline;
    padding:6px 0; border-bottom:1px solid #f0f2f5; font-size:.8rem;
}
.info-list li:last-child { border-bottom:none; }
.info-list .lbl { color:#a0aec0; font-weight:600; }
.info-list .val { color:#2d3748; text-align:right; }

/* ── History ────────────────────────────────────────────── */
.hist-item { display:flex; gap:10px; margin-bottom:10px; font-size:.77rem; }
.hist-dot {
    width:8px; height:8px; border-radius:50%; background:#3498db;
    flex-shrink:0; margin-top:4px;
}
.hist-time { color:#a0aec0; }
.hist-text { color:#4a5568; }

/* ── SLA ─────────────────────────────────────────────────── */
.sla-box {
    border-radius:8px; padding:12px 14px; margin-bottom:18px;
    border:1px solid #fde68a; background:#fffbeb; font-size:.82rem;
}
.sla-box.expired { border-color:#fca5a5; background:#fef2f2; }

/* ── Attachment pill ─────────────────────────────────────── */
.att-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 10px; border-radius:20px; font-size:.74rem;
    border:1px solid #bfdbfe; color:#3498db; background:#fff;
    text-decoration:none; transition:background .15s;
}
.att-pill:hover { background:#eff6ff; }
</style>
@endsection

@section('content')
@php
  $statusCls = ['open'=>'tk-badge-open','in_progress'=>'tk-badge-inprog','pending_user'=>'tk-badge-pending','forwarded'=>'tk-badge-fwd','resolved'=>'tk-badge-resolved','closed'=>'tk-badge-closed'];
  $priCls    = ['low'=>'tk-badge-low','medium'=>'tk-badge-medium','high'=>'tk-badge-high','critical'=>'tk-badge-critical'];
  $sLabel    = ['open'=>'🟢 Abierto','in_progress'=>'🟡 En Proceso','pending_user'=>'🟠 Pendiente Usuario','forwarded'=>'🔵 Derivado','resolved'=>'✅ Resuelto','closed'=>'⚫ Cerrado'];
  $pLabel    = ['low'=>'Baja','medium'=>'Media','high'=>'Alta','critical'=>'Crítica'];
@endphp

{{-- Breadcrumb + Volver --}}
<div class="page-header" style="display:flex; align-items:center; justify-content:space-between;">
    <div>
        <h1>Detalle del Ticket</h1>
        <div class="breadcrumb-bar">
            <a href="{{ route('home') }}">Inicio</a>
            <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
            <a href="{{ route('tickets.index') }}">Tickets de Soporte</a>
            <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
            <span style="color:#a0aec0;">#{{ $ticket->ticket_number }}</span>
        </div>
    </div>
    <a href="{{ route('tickets.index') }}" style="display:inline-flex; align-items:center; gap:6px; background:#1a2332; color:#fff; font-size:0.82rem; font-weight:600; padding:8px 18px; border-radius:7px; text-decoration:none; transition:background 0.2s; white-space:nowrap; flex-shrink:0;"
       onmouseover="this.style.background='#2d3748'" onmouseout="this.style.background='#1a2332'">
        &#8592; Volver
    </a>
</div>

<div class="ticket-page">

  {{-- ── MAIN ── --}}
  <div class="ticket-main">

    {{-- Header --}}
    <div class="tk-header">
        <div class="tk-num">{{ $ticket->ticket_number }}</div>
        <div class="tk-title">{{ $ticket->title }}</div>
        <div class="tk-meta">
            Creado por <strong>{{ $ticket->getCreatorName() }}</strong>
            @if($ticket->isGuestTicket()) <span style="font-size:.68rem;background:rgba(255,255,255,.15);color:#e2e8f0;padding:1px 8px;border-radius:10px;margin-left:4px;">Invitado</span>@endif
            &nbsp;·&nbsp; <i class="fas fa-building"></i> {{ $ticket->getCreatorDepartment() }}
            &nbsp;·&nbsp; {{ $ticket->created_at->format('d/m/Y H:i') }}
            &nbsp;·&nbsp; hace {{ $ticket->created_at->diffForHumans() }}
        </div>
        <div class="tk-badges">
            <span class="tk-badge {{ $statusCls[$ticket->status] ?? 'tk-badge-closed' }}">{{ $sLabel[$ticket->status] ?? $ticket->status }}</span>
            <span class="tk-badge {{ $priCls[$ticket->priority] ?? 'tk-badge-medium' }}">{{ $pLabel[$ticket->priority] ?? $ticket->priority }}</span>
            @if($ticket->assignedTo)
                <span class="tk-badge" style="background:rgba(255,255,255,.15);color:#e2e8f0;">👤 {{ $ticket->assignedTo->name }}</span>
            @else
                <span class="tk-badge" style="background:rgba(255,255,255,.08);color:#90a4ae;">Sin asignar</span>
            @endif
            <span class="tk-badge" style="background:rgba(255,255,255,.08);color:#90a4ae;">🏢 {{ $ticket->department->name ?? '—' }}</span>
        </div>
    </div>

    {{-- SLA --}}
    @if($ticket->status === 'pending_user' && $ticket->response_deadline_at)
    <div class="sla-box {{ $ticket->isResponseTimeExpired() ? 'expired' : '' }}">
        <i class="fas fa-clock"></i>
        @if($ticket->isResponseTimeExpired())
            <strong>⚠️ Tiempo de respuesta expirado.</strong> El ticket puede cerrarse automáticamente.
        @else
            <strong>Pendiente de respuesta.</strong> Plazo: {{ $ticket->response_deadline_at->format('d/m/Y H:i') }} ({{ $ticket->response_deadline_at->diffForHumans() }})
        @endif
    </div>
    @endif

    {{-- Descripción --}}
    <div class="tk-card">
        <div class="tk-card-header"><i class="fas fa-file-alt"></i> Descripción del Problema</div>
        <div class="tk-card-body">
            <div class="tk-desc">{!! $ticket->description !!}</div>
            @if($ticket->attachments->where('comment_id', null)->count() > 0)
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid #f0f2f5;">
                    @foreach($ticket->attachments->where('comment_id', null) as $att)
                        <a href="{{ asset('storage/'.$att->file_path) }}" class="att-pill" target="_blank">
                            <i class="fas fa-paperclip"></i> {{ $att->file_name }}
                            <span style="color:#a0aec0;">({{ number_format($att->file_size/1024,1) }} KB)</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Conversación --}}
    <div class="tk-card">
        <div class="tk-card-header">
            <i class="fas fa-comments"></i> Conversación
            <span style="margin-left:auto;font-size:.72rem;background:#e8ecf0;color:#718096;padding:2px 8px;border-radius:10px;font-weight:600;">
                {{ $ticket->comments->count() }} mensaje{{ $ticket->comments->count() !== 1 ? 's':'' }}
            </span>
        </div>
        <div class="tk-card-body">
            @if($ticket->comments->count() === 0)
                <p style="text-align:center;color:#a0aec0;padding:20px 0;font-size:.84rem;">
                    <i class="fas fa-comment-slash" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                    No hay mensajes aún. ¡Sé el primero en responder!
                </p>
            @else
                @foreach($ticket->comments as $comment)
                    @php
                        $isInt = $comment->is_internal;
                        $showIt = !$isInt || (Auth::check() && (Auth::user()->isSupport() || Auth::user()->isAdmin()));
                        $cu = $comment->user;
                    @endphp
                    @if($showIt)
                    <div class="comment-item {{ $isInt ? 'internal':'' }}">
                        <div class="comment-head">
                            <div class="c-avatar {{ $cu ? ($cu->isAdmin() ? 'av-a':($cu->isSupport()?'av-s':'av-u')):'av-u' }}">
                                {{ $cu ? strtoupper(substr($cu->name,0,1)):'?' }}
                            </div>
                            <div>
                                <span class="c-name">{{ $cu->name ?? 'Sistema' }}</span>
                                @if($cu)<span class="c-role">{{ ucfirst($cu->role) }}</span>@endif
                                @if($isInt)<span style="font-size:.68rem;background:#fef3c7;color:#92400e;padding:1px 7px;border-radius:10px;margin-left:5px;">🔒 Interno</span>@endif
                            </div>
                            <div class="c-time">{{ $comment->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                        <div class="c-body">{{ $comment->comment }}</div>
                        @if($comment->attachments && $comment->attachments->count() > 0)
                            <div style="display:flex;gap:5px;flex-wrap:wrap;margin-top:8px;">
                                @foreach($comment->attachments as $att)
                                    <a href="{{ asset('storage/'.$att->file_path) }}" class="att-pill" target="_blank">
                                        <i class="fas fa-paperclip"></i> {{ $att->file_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif
                @endforeach
            @endif

            {{-- Responder --}}
            @auth
            @if(!in_array($ticket->status, ['closed','resolved']) || Auth::user()->isAdmin())
            <div class="reply-wrap" style="margin-top:16px;">
                <form method="POST" action="/tickets/{{ $ticket->id }}/comment" enctype="multipart/form-data">
                    @csrf
                    <textarea name="comment" placeholder="Escribe tu respuesta..." required></textarea>
                    <div class="reply-footer">
                        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                            @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
                            <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:.79rem;color:#718096;">
                                <input type="checkbox" name="is_internal" value="1"> Comentario interno
                            </label>
                            @endif
                            <label style="display:flex;align-items:center;gap:5px;cursor:pointer;font-size:.79rem;color:#718096;">
                                <input type="file" name="attachments[]" multiple style="display:none;" id="commentFile"
                                       onchange="document.getElementById('commentFileName').textContent=Array.from(this.files).map(f=>f.name).join(', ')">
                                <span onclick="document.getElementById('commentFile').click()" style="cursor:pointer;color:#3498db;">
                                    <i class="fas fa-paperclip"></i> Adjuntar
                                </span>
                                <span id="commentFileName" style="color:#a0aec0;font-size:.74rem;"></span>
                            </label>
                        </div>
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Enviar respuesta
                        </button>
                    </div>
                </form>
            </div>
            @endif
            @endauth
        </div>
    </div>

  </div>{{-- /ticket-main --}}

  {{-- ── SIDEBAR ── --}}
  <div class="ticket-side">

    {{-- Acciones soporte/admin --}}
    @auth
    @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
    <div class="side-card">
        <div class="side-card-header"><i class="fas fa-tools me-1"></i> Acciones</div>
        <div class="side-card-body">

            {{-- Estado: solo admin o agente asignado puede cambiarlo --}}
            @if(Auth::user()->isAdmin() || $ticket->assigned_to === Auth::id())
            <form method="POST" action="/tickets/{{ $ticket->id }}/status" style="margin-bottom:12px;">
                @csrf @method('PUT')
                <label style="font-size:.74rem;color:#718096;font-weight:600;display:block;margin-bottom:5px;">Cambiar Estado</label>
                <select class="side-status-select" name="status" onchange="this.form.submit()">
                    <option value="open"         {{ $ticket->status==='open'         ?'selected':'' }}>🟢 Abierto</option>
                    <option value="in_progress"  {{ $ticket->status==='in_progress'  ?'selected':'' }}>🟡 En Proceso</option>
                    <option value="pending_user" {{ $ticket->status==='pending_user' ?'selected':'' }}>🟠 Pendiente Usuario</option>
                    <option value="forwarded"    {{ $ticket->status==='forwarded'    ?'selected':'' }}>🔵 Derivado</option>
                    <option value="resolved"     {{ $ticket->status==='resolved'     ?'selected':'' }}>✅ Resuelto</option>
                    <option value="closed"       {{ $ticket->status==='closed'       ?'selected':'' }}>⚫ Cerrado</option>
                </select>
            </form>
            @else
            {{-- Solo lectura para agentes sin permiso --}}
            <div style="margin-bottom:12px;">
                <label style="font-size:.74rem;color:#718096;font-weight:600;display:block;margin-bottom:5px;">Estado actual</label>
                <span class="tk-badge {{ $statusCls[$ticket->status] ?? 'tk-badge-closed' }}" style="font-size:.78rem;padding:5px 12px;">
                    {{ $sLabel[$ticket->status] ?? $ticket->status }}
                </span>
            </div>
            @endif

            {{-- Auto-asignarse --}}
            @if($ticket->assigned_to === Auth::id())
                <div class="mine-note"><i class="fas fa-check"></i> Asignado a ti</div>
            @elseif($ticket->assigned_to && !Auth::user()->isAdmin())
                <div class="lock-note"><i class="fas fa-lock"></i> Tomado por <strong style="margin-left:3px;">{{ $ticket->assignedTo->name }}</strong></div>
            @else
                <form method="POST" action="/tickets/{{ $ticket->id }}/self-assign" style="margin-bottom:10px;">
                    @csrf
                    <button type="submit" class="side-btn side-btn-green">
                        <i class="fas fa-hand-point-up"></i>
                        {{ $ticket->assigned_to ? 'Reasignarme':'Asignarme este ticket' }}
                    </button>
                </form>
            @endif

            {{-- Asignar a otro / Derivar: solo admin o el agente asignado --}}
            @if(Auth::user()->isAdmin() || $ticket->assigned_to === Auth::id())
            {{-- Asignar a otro --}}
            <button class="side-btn side-btn-outline" data-bs-toggle="modal" data-bs-target="#assignModal">
                <i class="fas fa-user-check"></i> Asignar a otro agente
            </button>

            {{-- Derivar --}}
            <button class="side-btn side-btn-outline" data-bs-toggle="modal" data-bs-target="#forwardModal">
                <i class="fas fa-share"></i> Derivar a departamento
            </button>

            {{-- Solicitar Información Adicional (RF-ST-15 / RNG-01) --}}
            @if(!in_array($ticket->status, ['closed','resolved','pending_user']))
            <button class="side-btn side-btn-outline" data-bs-toggle="modal" data-bs-target="#requestInfoModal"
                    style="border-color:#f59e0b;color:#f59e0b;margin-top:4px;">
                <i class="fas fa-question-circle"></i> Solicitar información
            </button>
            @elseif($ticket->status === 'pending_user')
            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;padding:8px 10px;font-size:.76rem;color:#92400e;margin-top:6px;">
                <i class="fas fa-clock"></i> Esperando respuesta del usuario
                @if($ticket->response_deadline_at)
                    <br><small>Vence: {{ $ticket->response_deadline_at->format('d/m H:i') }}</small>
                @endif
            </div>
            @endif
            @endif
        </div>
    </div>
    @endif
    @endauth

    {{-- Gestión avanzada: prioridad, clasificación, cierre formal --}}
    @auth
    @if((Auth::user()->isAdmin() || $ticket->assigned_to === Auth::id()) && $ticket->status !== 'closed')
    <div class="side-card">
        <div class="side-card-header"><i class="fas fa-sliders-h me-1"></i> Gestión del Ticket</div>
        <div class="side-card-body">

            {{-- Prioridad editable --}}
            <form method="POST" action="{{ route('tickets.updatePriority', $ticket) }}" style="margin-bottom:12px;">
                @csrf @method('PUT')
                <label style="font-size:.74rem;color:#718096;font-weight:600;display:block;margin-bottom:5px;">Prioridad</label>
                <div style="display:flex;gap:.4rem;">
                    <select name="priority" class="side-status-select" style="flex:1;">
                        <option value="low"      {{ $ticket->priority==='low'      ?'selected':'' }}>⚪ Baja</option>
                        <option value="medium"   {{ $ticket->priority==='medium'   ?'selected':'' }}>🔵 Media</option>
                        <option value="high"     {{ $ticket->priority==='high'     ?'selected':'' }}>🟠 Alta</option>
                        <option value="critical" {{ $ticket->priority==='critical' ?'selected':'' }}>🔴 Crítica</option>
                    </select>
                    <button type="submit" class="side-btn side-btn-outline" style="width:auto;padding:0 10px;" title="Guardar prioridad">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
            </form>

            {{-- Clasificación editable --}}
            <form method="POST" action="{{ route('tickets.updateClassification', $ticket) }}">
                @csrf @method('PUT')
                <label style="font-size:.74rem;color:#718096;font-weight:600;display:block;margin-bottom:5px;">Clasificación</label>
                @php
                    $allSubs = \App\Models\Subcategoria::with('categoria')
                        ->where('is_active', true)->orderBy('name')->get();
                @endphp
                <select name="subcategoria_id" id="sc-sub" class="side-status-select" style="margin-bottom:4px;"
                        onchange="loadTiposShow(this.value)">
                    <option value="">— Subcategoría —</option>
                    @foreach($allSubs as $s)
                    <option value="{{ $s->id }}" {{ $ticket->subcategoria_id == $s->id ? 'selected' : '' }}>
                        {{ $s->categoria->name ?? '' }} › {{ $s->name }}
                    </option>
                    @endforeach
                </select>
                <select name="tipo_incidente_id" id="sc-tipo" class="side-status-select" style="margin-bottom:8px;">
                    <option value="">— Tipo de Incidente —</option>
                    @if($ticket->tipoIncidente)
                    <option value="{{ $ticket->tipoIncidente->id }}" selected>{{ $ticket->tipoIncidente->name }}</option>
                    @endif
                </select>
                <button type="submit" class="side-btn side-btn-outline">
                    <i class="fas fa-tag"></i> Actualizar clasificación
                </button>
            </form>
        </div>
    </div>

    {{-- Cierre formal con solución (RF-ST-10, RF-ST-14) --}}
    <div class="side-card" style="border-left:3px solid #8b5cf6;">
        <div class="side-card-header" style="color:#5b21b6;"><i class="fas fa-check-circle me-1"></i> Cerrar Ticket</div>
        <div class="side-card-body">
            @if($ticket->solution_text)
            <div style="font-size:.8rem;color:#4a5568;background:#f5f3ff;padding:.6rem .8rem;border-radius:.4rem;margin-bottom:.6rem;">
                <strong>Solución registrada:</strong><br>{{ $ticket->solution_text }}
            </div>
            @endif
            <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                @csrf
                <label style="font-size:.74rem;color:#718096;font-weight:600;display:block;margin-bottom:5px;">
                    Solución Aplicada *
                </label>
                <textarea name="solution_text" rows="3"
                          class="side-status-select" style="height:auto;resize:vertical;font-size:.82rem;padding:.5rem;"
                          placeholder="Describe la solución antes de cerrar…" required>{{ $ticket->solution_text }}</textarea>
                <button type="submit" class="side-btn" style="background:#8b5cf6;color:#fff;margin-top:8px;"
                        onclick="return confirm('¿Confirmas el cierre formal del ticket?')">
                    <i class="fas fa-lock"></i> Cerrar con solución
                </button>
            </form>
        </div>
    </div>
    @endif
    @endauth

    {{-- Confirmar resolución: el usuario puede cerrar su propio ticket resuelto (RF-RI-01) --}}
    @auth
    @if(Auth::user()->isUser() && $ticket->user_id === Auth::id() && $ticket->status === 'resolved')
    <div class="side-card" style="border-left:3px solid #22c55e;">
        <div class="side-card-header" style="color:#065f46;"><i class="fas fa-check-double me-1"></i> Confirmar Resolución</div>
        <div class="side-card-body">
            <p style="font-size:.8rem;color:#4a5568;margin-bottom:10px;">
                El equipo de soporte marcó tu ticket como <strong>Resuelto</strong>. ¿El problema fue solucionado?
            </p>
            <form method="POST" action="{{ route('tickets.close', $ticket) }}">
                @csrf
                <input type="hidden" name="solution_text" value="{{ $ticket->solution_text ?? 'Confirmado por el solicitante.' }}">
                <button type="submit" class="side-btn" style="background:#22c55e;color:#fff;margin-bottom:8px;"
                        onclick="return confirm('¿Confirmas que el problema fue resuelto?')">
                    <i class="fas fa-check-circle"></i> Sí, confirmar y cerrar
                </button>
            </form>
            <a href="#commentForm" class="side-btn side-btn-outline" style="font-size:.78rem;padding:6px 10px;text-align:center;display:block;">
                <i class="fas fa-comment"></i> No, necesito más ayuda
            </a>
        </div>
    </div>
    @endif
    @endauth

    <div class="side-card">
        <div class="side-card-header"><i class="fas fa-info-circle me-1"></i> Información</div>
        <div class="side-card-body" style="padding:10px 16px;">
            <ul class="info-list">
                <li><span class="lbl">Clasificación</span><span class="val" style="font-size:.78rem;">{{ $ticket->getClassificationLabel() }}</span></li>
                <li><span class="lbl">Dispositivo</span><span class="val">{{ ucfirst($ticket->device_type) }}</span></li>
                <li><span class="lbl">Departamento</span><span class="val">{{ $ticket->department->name ?? '—' }}</span></li>
                <li><span class="lbl">Prioridad</span>
                    <span class="val">
                        <span class="tk-badge {{ $priCls[$ticket->priority] ?? 'tk-badge-medium' }}" style="padding:2px 8px;">{{ $pLabel[$ticket->priority] ?? $ticket->priority }}</span>
                    </span>
                </li>
                <li><span class="lbl">Tiempo total</span><span class="val">{{ $ticket->getTimeElapsedFormatted() }}</span></li>
                <li><span class="lbl">T. soporte</span>
                    <span class="val" title="Excluye espera de respuesta del usuario">{{ $ticket->getSupportTimeFormatted() }}</span>
                </li>
                @if($ticket->sla_resolution_deadline_at)
                <li><span class="lbl">SLA vence</span>
                    <span class="val" style="font-size:.78rem;color:{{ $ticket->getSlaResolutionStatus()==='exceeded'?'#ef4444':($ticket->getSlaResolutionStatus()==='warning'?'#f59e0b':'#22c55e') }}">
                        @if($ticket->getSlaResolutionStatus()==='exceeded') ⚠ Vencido
                        @elseif($ticket->getSlaResolutionStatus()==='warning') ⏰ Por vencer
                        @else ✓ {{ $ticket->sla_resolution_deadline_at->format('d/m/Y H:i') }}
                        @endif
                    </span>
                </li>
                @endif
                <li><span class="lbl">Creado</span><span class="val">{{ $ticket->created_at->format('d/m/Y H:i') }}</span></li>
                <li><span class="lbl">Actualizado</span><span class="val">{{ $ticket->updated_at->format('d/m/Y H:i') }}</span></li>
                @if($ticket->closed_at)
                <li><span class="lbl">Cerrado</span><span class="val">{{ $ticket->closed_at->format('d/m/Y H:i') }}</span></li>
                @endif
            </ul>
        </div>
    </div>

    {{-- Historial --}}
    @if($ticket->history && $ticket->history->count() > 0)
    <div class="side-card">
        <div class="side-card-header"><i class="fas fa-history me-1"></i> Historial</div>
        <div class="side-card-body" style="padding:12px 16px;">
            @foreach($ticket->history->take(10) as $entry)
            <div class="hist-item">
                <div class="hist-dot"></div>
                <div>
                    <div class="hist-time">{{ $entry->created_at->format('d/m H:i') }}</div>
                    <div class="hist-text">
                        @php
                            $sNames = ['open'=>'Abierto','in_progress'=>'En Proceso','pending_user'=>'Pendiente Usuario','forwarded'=>'Derivado','resolved'=>'Resuelto','closed'=>'Cerrado'];
                        @endphp
                        @if($entry->action === 'status_change')
                            Estado: {{ $sNames[$entry->old_value] ?? $entry->old_value }} → {{ $sNames[$entry->new_value] ?? $entry->new_value }}
                        @elseif(in_array($entry->action,['assigned','self_assigned'])) {{ $entry->action === 'self_assigned' ? 'Auto-asignado':'Asignado' }}
                        @elseif($entry->action === 'forwarded') Derivado: {{ $entry->old_value }} → {{ $entry->new_value }}
                        @elseif($entry->action === 'auto_closed') Cerrado automáticamente
                        @elseif($entry->action === 'user_responded') Usuario respondió
                        @elseif($entry->action === 'requested_info') ❓ Información adicional solicitada al usuario
                        @elseif($entry->action === 'closed') Ticket cerrado
                        @else {{ str_replace('_', ' ', ucfirst($entry->action)) }}
                        @endif
                        @if($entry->user) <span style="color:#a0aec0;">· {{ $entry->user->name }}</span>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

  </div>{{-- /ticket-side --}}

</div>{{-- /ticket-page --}}

{{-- Modal Asignar --}}
@auth
@if(Auth::user()->isSupport() || Auth::user()->isAdmin())
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/tickets/{{ $ticket->id }}/assign">
            @csrf
            <div class="modal-content" style="border-radius:10px;border:none;">
                <div class="modal-header" style="background:linear-gradient(90deg,#1a2332,#243447);border-radius:10px 10px 0 0;border:none;">
                    <h5 class="modal-title" style="color:#fff;font-size:.9rem;"><i class="fas fa-user-check me-2"></i>Asignar Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <label style="font-size:.82rem;font-weight:600;color:#4a5568;display:block;margin-bottom:6px;">Seleccionar agente</label>
                    <select class="form-select" name="user_id" required style="border-radius:7px;border-color:#e2e8f0;">
                        <option value="">-- Selecciona --</option>
                        @foreach($supportUsers as $u)
                            <option value="{{ $u->id }}" {{ $ticket->assigned_to===$u->id?'selected':'' }}>
                                {{ $u->name }} ({{ ucfirst($u->role) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f2f5;padding:12px 20px;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-send" style="margin:0;">Asignar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endauth

{{-- Modal Derivar --}}
<div class="modal fade" id="forwardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/tickets/{{ $ticket->id }}/forward">
            @csrf
            <div class="modal-content" style="border-radius:10px;border:none;">
                <div class="modal-header" style="background:linear-gradient(90deg,#1a2332,#243447);border-radius:10px 10px 0 0;border:none;">
                    <h5 class="modal-title" style="color:#fff;font-size:.9rem;"><i class="fas fa-share me-2"></i>Derivar Ticket</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <div class="mb-3">
                        <label style="font-size:.82rem;font-weight:600;color:#4a5568;display:block;margin-bottom:6px;">Departamento destino</label>
                        <select class="form-select" name="department_id" required style="border-radius:7px;border-color:#e2e8f0;">
                            <option value="">-- Selecciona --</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $ticket->department_id===$dept->id?'disabled':'' }}>
                                    {{ $dept->name }} {{ $ticket->department_id===$dept->id?'(actual)':'' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.82rem;font-weight:600;color:#4a5568;display:block;margin-bottom:6px;">Nota (opcional)</label>
                        <textarea class="form-control" name="comment" rows="3" placeholder="Razón de la derivación..." style="border-radius:7px;border-color:#e2e8f0;font-size:.83rem;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f2f5;padding:12px 20px;">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-send" style="margin:0;"><i class="fas fa-share me-1"></i>Derivar</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Solicitar Información Adicional (RF-ST-15 / RNG-01) --}}
@auth
@if(Auth::user()->isSupport() || Auth::user()->isAdmin())
<div class="modal fade" id="requestInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:12px;border:none;">
            <div class="modal-header" style="background:linear-gradient(90deg,#92400e,#b45309);border-radius:12px 12px 0 0;padding:14px 20px;">
                <h5 class="modal-title" style="color:#fff;font-size:.95rem;font-weight:600;margin:0;">
                    <i class="fas fa-question-circle me-2" style="color:#fcd34d;"></i>Solicitar Información Adicional
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px;">
                <p style="font-size:.84rem;color:#4a5568;margin-bottom:14px;">
                    Al solicitar información, el ticket cambiará a <strong>Pendiente Usuario</strong> y el solicitante
                    tendrá <strong>2 horas</strong> para responder antes del cierre automático.
                </p>
                <form method="POST" action="{{ route('tickets.addComment', $ticket) }}" enctype="multipart/form-data">
                    @csrf
                    {{-- Campo oculto para cambiar estado a pending_user --}}
                    <input type="hidden" name="request_info" value="1">
                    <div style="margin-bottom:12px;">
                        <label style="font-size:.82rem;font-weight:600;color:#4a5568;display:block;margin-bottom:5px;">
                            Mensaje al solicitante *
                        </label>
                        <textarea name="comment" rows="4" required
                                  style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:10px;font-size:.84rem;resize:vertical;"
                                  placeholder="Describe qué información necesitas del solicitante..."></textarea>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;font-weight:600;padding:7px 18px;border-radius:6px;border:none;">
                            <i class="fas fa-paper-plane me-1"></i> Enviar solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endauth

@push('scripts')
<script>
function loadTiposShow(subId) {
    const sel = document.getElementById('sc-tipo');
    sel.innerHTML = '<option value="">— Tipo de Incidente —</option>';
    if (!subId) return;
    fetch('/api/subcategorias/' + subId + '/tipos')
        .then(r => r.json())
        .then(data => {
            data.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                sel.appendChild(opt);
            });
        });
}
</script>
@endpush
@endsection
