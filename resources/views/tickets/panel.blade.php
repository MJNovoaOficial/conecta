<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',system-ui,sans-serif;font-size:0.875rem;color:#2d3748;background:#fff;}

/* Header */
.pnl-header{background:linear-gradient(135deg,#1a2332,#2c3e50);color:#fff;padding:18px 20px;position:sticky;top:0;z-index:10;}
.pnl-num{font-size:0.72rem;color:#90a4ae;font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:4px;}
.pnl-title{font-size:1rem;font-weight:700;line-height:1.3;margin-bottom:10px;}
.pnl-badges{display:flex;gap:8px;flex-wrap:wrap;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;}
.badge-open{background:#d1fae5;color:#065f46;}
.badge-in_progress{background:#fef3c7;color:#92400e;}
.badge-pending_user{background:#ffedd5;color:#9a3412;}
.badge-forwarded{background:#dbeafe;color:#1e40af;}
.badge-resolved{background:#ede9fe;color:#5b21b6;}
.badge-closed{background:#f1f5f9;color:#475569;}
.badge-low{background:#f0fdf4;color:#166534;}
.badge-medium{background:#fefce8;color:#854d0e;}
.badge-high{background:#fff7ed;color:#9a3412;}
.badge-critical{background:#fef2f2;color:#991b1b;}

/* Meta row */
.pnl-meta{display:flex;gap:16px;flex-wrap:wrap;padding:12px 20px;background:#f7f9fc;border-bottom:1px solid #e8ecf0;font-size:0.78rem;color:#718096;}
.pnl-meta span{display:flex;align-items:center;gap:4px;}

/* Body */
.pnl-body{padding:18px 20px;}

/* Description */
.pnl-section{margin-bottom:18px;}
.pnl-section-title{font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a0aec0;margin-bottom:8px;}
.pnl-desc{background:#f7f9fc;border-left:3px solid #3498db;border-radius:0 6px 6px 0;padding:12px 14px;font-size:0.85rem;line-height:1.6;color:#2d3748;white-space:pre-wrap;}

/* Acciones soporte */
.pnl-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;}
.btn-act{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border-radius:6px;font-size:0.78rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:all .15s;}
.btn-act-green{background:#27ae60;color:#fff;} .btn-act-green:hover{background:#1e8449;color:#fff;}
.btn-act-blue{background:#3498db;color:#fff;} .btn-act-blue:hover{background:#2980b9;color:#fff;}
.btn-act-outline{background:#fff;color:#4a5568;border:1px solid #e2e8f0;} .btn-act-outline:hover{background:#f7f9fc;}
.btn-act-warn{background:#f39c12;color:#fff;} .btn-act-warn:hover{background:#d68910;color:#fff;}

.lock-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:0.78rem;font-weight:600;}

/* Estado select */
.status-form{display:flex;align-items:center;gap:8px;margin-bottom:14px;}
.status-form label{font-size:0.78rem;color:#718096;font-weight:600;white-space:nowrap;}
.status-form select{padding:5px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:0.78rem;color:#2d3748;background:#fff;cursor:pointer;}

/* Comments */
.comment-item{border-left:3px solid #e2e8f0;padding:10px 14px;margin-bottom:10px;border-radius:0 6px 6px 0;background:#fff;}
.comment-item.internal{border-left-color:#f39c12;background:#fffbeb;}
.comment-author{display:flex;align-items:center;gap:8px;margin-bottom:6px;}
.avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.75rem;color:#fff;flex-shrink:0;}
.av-user{background:linear-gradient(135deg,#3498db,#2980b9);}
.av-support{background:linear-gradient(135deg,#27ae60,#2ecc71);}
.av-admin{background:linear-gradient(135deg,#e74c3c,#c0392b);}
.comment-who{font-size:0.78rem;font-weight:600;color:#1a2332;}
.comment-when{font-size:0.72rem;color:#a0aec0;}
.comment-text{font-size:0.82rem;color:#4a5568;line-height:1.5;white-space:pre-wrap;}

/* Reply form */
.reply-form{background:#f7f9fc;border:1px solid #e8ecf0;border-radius:8px;padding:14px;margin-top:14px;}
.reply-form textarea{width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:9px 12px;font-size:0.83rem;resize:vertical;min-height:80px;outline:none;font-family:inherit;}
.reply-form textarea:focus{border-color:#3498db;box-shadow:0 0 0 3px rgba(52,152,219,.1);}
.reply-footer{display:flex;align-items:center;justify-content:space-between;margin-top:8px;flex-wrap:wrap;gap:8px;}
.reply-opts{display:flex;align-items:center;gap:6px;font-size:0.78rem;color:#718096;}
.btn-send{background:#1a2332;color:#fff;border:none;padding:7px 18px;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;transition:background .2s;}
.btn-send:hover{background:#2d3748;}

/* Open full */
.open-full{display:flex;justify-content:flex-end;padding:12px 20px;border-top:1px solid #f0f2f5;}
.open-full a{font-size:0.78rem;color:#3498db;text-decoration:none;display:flex;align-items:center;gap:4px;}
.open-full a:hover{text-decoration:underline;}

/* Assign inline mini */
.assign-section{background:#f7f9fc;border:1px solid #e8ecf0;border-radius:6px;padding:10px 12px;margin-bottom:10px;}
.assign-section label{font-size:0.75rem;color:#718096;font-weight:600;display:block;margin-bottom:4px;}
.assign-section select{width:100%;padding:5px 8px;border:1px solid #e2e8f0;border-radius:5px;font-size:0.78rem;}
</style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="pnl-header">
  <div class="pnl-num">#{{ $ticket->ticket_number }}</div>
  <div class="pnl-title">{{ $ticket->title }}</div>
  <div class="pnl-badges">
    @php
      $sCls = ['open'=>'badge-open','in_progress'=>'badge-in_progress','pending_user'=>'badge-pending_user','forwarded'=>'badge-forwarded','resolved'=>'badge-resolved','closed'=>'badge-closed'];
      $pCls = ['low'=>'badge-low','medium'=>'badge-medium','high'=>'badge-high','critical'=>'badge-critical'];
      $sLabel = ['open'=>'Abierto','in_progress'=>'En Proceso','pending_user'=>'Pendiente Usuario','forwarded'=>'Derivado','resolved'=>'Resuelto','closed'=>'Cerrado'];
      $pLabel = ['low'=>'Baja','medium'=>'Media','high'=>'Alta','critical'=>'Crítica'];
    @endphp
    <span class="badge {{ $sCls[$ticket->status] ?? 'badge-closed' }}">{{ $sLabel[$ticket->status] ?? $ticket->status }}</span>
    <span class="badge {{ $pCls[$ticket->priority] ?? 'badge-medium' }}">{{ $pLabel[$ticket->priority] ?? $ticket->priority }}</span>
    @if($ticket->assignedTo)
      <span class="badge" style="background:#e0f2fe;color:#0369a1;">👤 {{ $ticket->assignedTo->name }}</span>
    @else
      <span class="badge" style="background:#f1f5f9;color:#64748b;">Sin asignar</span>
    @endif
  </div>
</div>

{{-- ── META ── --}}
<div class="pnl-meta">
  <span>🏢 {{ $ticket->department->name ?? '—' }}</span>
  <span>👤 {{ $ticket->getCreatorName() }}</span>
  <span>🗂 {{ ucfirst($ticket->category) }}</span>
  <span>💻 {{ ucfirst($ticket->device_type) }}</span>
  <span>🕐 {{ $ticket->created_at->format('d/m/Y H:i') }}</span>
</div>

{{-- ── BODY ── --}}
<div class="pnl-body">

  {{-- Acciones de soporte/admin --}}
  @auth
  @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
    {{-- Cambiar estado --}}
    <form method="POST" action="/tickets/{{ $ticket->id }}/status" class="status-form">
      @csrf @method('PUT')
      <label>Estado:</label>
      <select name="status" onchange="this.form.submit()">
        <option value="open"         {{ $ticket->status==='open'         ? 'selected':'' }}>🟢 Abierto</option>
        <option value="in_progress"  {{ $ticket->status==='in_progress'  ? 'selected':'' }}>🟡 En Proceso</option>
        <option value="pending_user" {{ $ticket->status==='pending_user' ? 'selected':'' }}>🟠 Pendiente Usuario</option>
        <option value="forwarded"    {{ $ticket->status==='forwarded'    ? 'selected':'' }}>🔵 Derivado</option>
        <option value="resolved"     {{ $ticket->status==='resolved'     ? 'selected':'' }}>✅ Resuelto</option>
        <option value="closed"       {{ $ticket->status==='closed'       ? 'selected':'' }}>⚫ Cerrado</option>
      </select>
    </form>

    {{-- Asignación --}}
    @if($ticket->assigned_to === Auth::id())
      <div class="lock-badge" style="background:#d1fae5;border-color:#6ee7b7;color:#065f46;margin-bottom:10px;">✅ Asignado a ti</div>
    @elseif($ticket->assigned_to && !Auth::user()->isAdmin())
      <div class="lock-badge" style="margin-bottom:10px;">🔒 Tomado por <strong style="margin-left:4px;">{{ $ticket->assignedTo->name }}</strong></div>
    @else
      <form method="POST" action="/tickets/{{ $ticket->id }}/self-assign" style="margin-bottom:10px;">
        @csrf
        <button type="submit" class="btn-act btn-act-green">
          ✋ {{ $ticket->assigned_to ? 'Reasignarme' : 'Asignarme este ticket' }}
        </button>
      </form>
    @endif

    {{-- Asignar a otro --}}
    <div class="assign-section">
      <form method="POST" action="/tickets/{{ $ticket->id }}/assign">
        @csrf
        <label>Asignar a otro agente:</label>
        <div style="display:flex;gap:6px;margin-top:4px;">
          <select name="user_id" style="flex:1;padding:5px 8px;border:1px solid #e2e8f0;border-radius:5px;font-size:0.78rem;">
            <option value="">-- Seleccionar --</option>
            @foreach($supportUsers as $su)
              <option value="{{ $su->id }}" {{ $ticket->assigned_to === $su->id ? 'selected':'' }}>
                {{ $su->name }} ({{ ucfirst($su->role) }})
              </option>
            @endforeach
          </select>
          <button type="submit" class="btn-act btn-act-blue" style="padding:5px 12px;">Asignar</button>
        </div>
      </form>
    </div>
  @endif
  @endauth

  {{-- Descripción --}}
  <div class="pnl-section">
    <div class="pnl-section-title">📄 Descripción</div>
    <div class="pnl-desc">{!! $ticket->description !!}</div>
    @if($ticket->attachments->where('comment_id', null)->count() > 0)
      <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap;">
        @foreach($ticket->attachments->where('comment_id', null) as $att)
          <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank"
             style="font-size:0.75rem;color:#3498db;border:1px solid #bfdbfe;border-radius:5px;padding:3px 9px;text-decoration:none;">
            📎 {{ $att->file_name }}
          </a>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Comentarios --}}
  <div class="pnl-section">
    <div class="pnl-section-title">💬 Conversación ({{ $ticket->comments->count() }})</div>

    @if($ticket->comments->count() === 0)
      <p style="color:#a0aec0;font-size:0.82rem;text-align:center;padding:12px 0;">Sin comentarios aún.</p>
    @else
      @foreach($ticket->comments as $comment)
        @php
          $isInternal = $comment->is_internal;
          $showIt = !$isInternal || (Auth::check() && (Auth::user()->isSupport() || Auth::user()->isAdmin()));
        @endphp
        @if($showIt)
        <div class="comment-item {{ $isInternal ? 'internal' : '' }}">
          <div class="comment-author">
            @php $cu = $comment->user; @endphp
            <div class="avatar {{ $cu ? ($cu->isAdmin() ? 'av-admin' : ($cu->isSupport() ? 'av-support' : 'av-user')) : 'av-user' }}">
              {{ $cu ? strtoupper(substr($cu->name,0,1)) : '?' }}
            </div>
            <div>
              <div class="comment-who">{{ $cu->name ?? 'Sistema' }}
                @if($isInternal)<span style="font-size:0.68rem;background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:10px;margin-left:4px;">🔒 Interno</span>@endif
              </div>
              <div class="comment-when">{{ $comment->created_at->format('d/m/Y H:i') }} · {{ $comment->created_at->diffForHumans() }}</div>
            </div>
          </div>
          <div class="comment-text">{{ $comment->comment }}</div>
          @if($comment->attachments && $comment->attachments->count() > 0)
            <div style="margin-top:6px;display:flex;gap:5px;flex-wrap:wrap;">
              @foreach($comment->attachments as $att)
                <a href="{{ asset('storage/'.$att->file_path) }}" target="_blank"
                   style="font-size:0.72rem;color:#3498db;border:1px solid #bfdbfe;border-radius:4px;padding:2px 7px;text-decoration:none;">
                  📎 {{ $att->file_name }}
                </a>
              @endforeach
            </div>
          @endif
        </div>
        @endif
      @endforeach
    @endif

    {{-- Formulario de respuesta --}}
    @auth
    @if(!in_array($ticket->status, ['closed','resolved']) || Auth::user()->isAdmin())
    <div class="reply-form">
      <form method="POST" action="/tickets/{{ $ticket->id }}/comment" enctype="multipart/form-data">
        @csrf
        <textarea name="comment" placeholder="Escribe tu respuesta..." required></textarea>
        <div class="reply-footer">
          <div class="reply-opts">
            @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
              <label style="display:flex;align-items:center;gap:4px;cursor:pointer;">
                <input type="checkbox" name="is_internal" value="1"> Comentario interno
              </label>
            @endif
          </div>
          <button type="submit" class="btn-send">✉️ Enviar respuesta</button>
        </div>
      </form>
    </div>
    @endif
    @endauth

  </div>
</div>

{{-- Ver ticket completo --}}
<div class="open-full">
  <a href="{{ route('tickets.show', $ticket) }}" target="_blank">
    🔗 Ver ticket completo ↗
  </a>
</div>

</body>
</html>
