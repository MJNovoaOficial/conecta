@extends('layouts.app')

@section('title', 'Mis Tickets de Soporte')

@section('content')
<div class="page-wrapper">

    {{-- SIDEBAR --}}
    <aside class="sidebar">

        {{-- Filtros por estado --}}
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-filter me-2"></i>Ver</span>
                <i class="fas fa-chevron-up toggle-icon"></i>
            </div>
            <a href="{{ route('tickets.index') }}" class="sidebar-item {{ !request('status') ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> Todos
                </div>
                <span class="sidebar-badge">{{ $counts['total'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'open']) }}" class="sidebar-item {{ request('status') == 'open' ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> Abierto
                </div>
                <span class="sidebar-badge badge-open">{{ $counts['open'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'in_progress']) }}" class="sidebar-item {{ request('status') == 'in_progress' ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> En Progreso
                </div>
                <span class="sidebar-badge badge-pending">{{ $counts['in_progress'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'pending_user']) }}" class="sidebar-item {{ request('status') == 'pending_user' ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> Resp. Cliente
                </div>
                <span class="sidebar-badge badge-pending">{{ $counts['pending_user'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'forwarded']) }}" class="sidebar-item {{ request('status') == 'forwarded' ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> Derivado
                </div>
                <span class="sidebar-badge">{{ $counts['forwarded'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'resolved']) }}" class="sidebar-item {{ request('status') == 'resolved' ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> Resuelto
                </div>
                <span class="sidebar-badge">{{ $counts['resolved'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['status' => 'closed']) }}" class="sidebar-item {{ request('status') == 'closed' ? 'active' : '' }}">
                <div class="item-left">
                    <div class="item-dot"></div> Cerrado
                </div>
                <span class="sidebar-badge">{{ $counts['closed'] ?? 0 }}</span>
            </a>
        </div>

        {{-- Navegación de soporte --}}
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-headset me-2"></i>Soporte</span>
            </div>
            <a href="{{ route('tickets.index') }}" class="sidebar-item active">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-ticket-alt"></i></span>
                    Mis Tickets
                </div>
            </a>
            <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#newTicketModal" onclick="return false;">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-plus-circle"></i></span>
                    Abrir Ticket
                </div>
            </a>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="sidebar-item">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-cog"></i></span>
                    Administración
                </div>
            </a>
            @endif
        </div>

    </aside>

    {{-- MAIN CONTENT --}}
    <div class="main-content">

        <div class="page-header">
            <h1>
                @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
                    Todos los Tickets de Soporte
                @else
                    Mis Tickets de Soporte
                @endif
                <small style="font-size:0.9rem; font-weight:400; color:#718096; margin-left:8px;">Su historial de tickets.</small>
            </h1>
            <div class="breadcrumb-bar">
                <a href="{{ route('home') }}">Inicio</a>
                <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                <span>Tickets de Soporte</span>
            </div>
        </div>

        <div class="content-card">
            <div class="content-card-header">
                <span class="header-info">
                    Mostrando {{ $tickets->firstItem() ?? 0 }} a {{ $tickets->lastItem() ?? 0 }} de {{ $tickets->total() ?? 0 }} entradas
                </span>
                <div class="content-card-search">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Buscar ticket..." onkeyup="filterTable(this.value)">
                </div>
            </div>

            @if($tickets->count() > 0)
            <div style="overflow-x:auto;">
                <table class="ticket-table" id="ticketTable">
                    <thead>
                        <tr>
                            <th>Departamento <span class="sort-icon">⇅</span></th>
                            <th>Asunto <span class="sort-icon">⇅</span></th>
                            <th>Estado <span class="sort-icon">⇅</span></th>
                            <th>Prioridad</th>
                            <th>Asignado</th>
                            <th>Última Actualización <span class="sort-icon">⇅</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        <tr onclick="window.location='{{ route('tickets.show', $ticket) }}'" style="cursor:pointer;">
                            <td class="ticket-dept">{{ $ticket->department->name ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" class="ticket-subject-link" onclick="event.stopPropagation();">
                                    #{{ $ticket->ticket_number }}
                                </a>
                                <div class="ticket-subject-sub">{{ Str::limit($ticket->title, 55) }}</div>
                            </td>
                            <td>
                                @php
                                    $statusClasses = [
                                        'open'         => 'status-open',
                                        'in_progress'  => 'status-in-progress',
                                        'pending_user' => 'status-pending',
                                        'forwarded'    => 'status-forwarded',
                                        'resolved'     => 'status-resolved',
                                        'closed'       => 'status-closed',
                                    ];
                                    $cls = $statusClasses[$ticket->status] ?? 'status-closed';
                                @endphp
                                <span class="status-badge {{ $cls }}">
                                    {{ $ticket->getStatusLabel() }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $priClasses = [
                                        'low' => 'priority-low',
                                        'medium' => 'priority-medium',
                                        'high' => 'priority-high',
                                        'critical' => 'priority-critical',
                                    ];
                                    $priCls = $priClasses[$ticket->priority] ?? 'priority-medium';
                                @endphp
                                <span class="priority-badge {{ $priCls }}">{{ $ticket->getPriorityLabel() }}</span>
                            </td>
                            <td style="font-size:0.8rem; color:#718096;">
                                {{ $ticket->assignedTo->name ?? '—' }}
                            </td>
                            <td style="font-size:0.8rem; color:#718096; white-space:nowrap;">
                                {{ $ticket->updated_at->format('d/m/Y (H:i)') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
            <div style="padding: 12px 18px; border-top: 1px solid #f0f2f5;">
                {{ $tickets->links() }}
            </div>
            @endif

            @else
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <h5>No hay tickets</h5>
                <p>No tienes tickets de soporte aún.</p>
                <a href="#" class="btn-submit-ticket" style="text-decoration:none; display:inline-block; margin-top:8px;"
                   data-bs-toggle="modal" data-bs-target="#newTicketModal" onclick="return false;">
                    <i class="fas fa-plus me-1"></i> Abrir primer ticket
                </a>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('styles')
<style>
.modal-cat-label:has(input:checked){border-color:#3498db;color:#2980b9;background:#ebf5fb;}
#modalMsgBox:focus-within{border-color:#3498db!important;box-shadow:0 0 0 3px rgba(52,152,219,.12);}
.fmt-btn{background:none;border:1px solid transparent;padding:3px 8px;border-radius:4px;cursor:pointer;font-size:.82rem;color:#4a5568;transition:all .15s;}
.fmt-btn:hover{background:#e2e8f0;border-color:#cbd5e0;}
#ticketTable tbody tr:hover{background:#f0f7ff !important;}
</style>
@endpush

@section('scripts')
<script>
function filterTable(q) {
    const rows = document.querySelectorAll('#ticketTable tbody tr');
    q = q.toLowerCase();
    rows.forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

function mfmt(cmd) {
    document.getElementById('modalEditor').focus();
    document.execCommand(cmd, false, null);
    syncModalEditor();
}
function syncModalEditor() {
    document.getElementById('modalDescField').value = document.getElementById('modalEditor').innerHTML;
}
function submitModalTicket() {
    syncModalEditor();
    const text = document.getElementById('modalEditor').innerText.trim();
    if (!text) { document.getElementById('modalDescField').value = ''; }
    document.getElementById('modalTicketForm').submit();
}
document.getElementById('newTicketModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTicketForm').reset();
    document.getElementById('modalEditor').innerHTML = '';
    document.getElementById('modalDescField').value = '';
    document.getElementById('modalFileNames').textContent = '';
});
</script>
@endsection

<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:12px; border:none; box-shadow:0 10px 40px rgba(0,0,0,0.15);">

      {{-- HEADER --}}
      <div class="modal-header" style="background:linear-gradient(90deg,#1a2332,#243447); border-radius:12px 12px 0 0; padding:16px 22px;">
        <h5 class="modal-title" style="color:#fff; font-size:1rem; font-weight:600; margin:0;" id="newTicketModalLabel">
          <i class="fas fa-plus-circle me-2" style="color:#3498db;"></i>Abrir Nuevo Ticket
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      {{-- BODY --}}
      <div class="modal-body" style="padding:24px;">
        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="modalTicketForm">
          @csrf

          {{-- Asunto --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Asunto *</label>
            <input type="text" name="title" class="form-control" placeholder="Describe brevemente el problema..." required
                   style="border-radius:7px; border-color:#e2e8f0; font-size:0.87rem;">
          </div>

          {{-- Departamento / Prioridad / Dispositivo --}}
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Departamento *</label>
              <select name="department_id" class="form-select" required style="border-radius:7px; border-color:#e2e8f0; font-size:0.87rem;">
                <option value="">Seleccionar...</option>
                @foreach($departments as $dept)
                  <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Prioridad *</label>
              <select name="priority" class="form-select" style="border-radius:7px; border-color:#e2e8f0; font-size:0.87rem;">
                <option value="low">Baja</option>
                <option value="medium" selected>Media</option>
                <option value="high">Alta</option>
                <option value="critical">Crítica</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Dispositivo *</label>
              <select name="device_type" class="form-select" required style="border-radius:7px; border-color:#e2e8f0; font-size:0.87rem;">
                <option value="">Seleccionar...</option>
                <option value="laptop">Laptop</option>
                <option value="desktop">Desktop</option>
                <option value="tablet">Tablet</option>
                <option value="phone">Teléfono</option>
                <option value="printer">Impresora</option>
                <option value="other">Otro</option>
              </select>
            </div>
          </div>

          {{-- Categoría --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Categoría *</label>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              @foreach(['Hardware'=>'Hardware','Software'=>'Software','Red/Internet'=>'Red/Internet','Cuenta/Acceso'=>'Cuenta/Acceso','Otro'=>'Otro'] as $val => $label)
              <label class="modal-cat-label" style="display:flex; align-items:center; gap:5px; padding:6px 12px; border:1.5px solid #e2e8f0; border-radius:6px; cursor:pointer; font-size:0.81rem; color:#4a5568; background:#fff;">
                <input type="radio" name="category" value="{{ $val }}" style="accent-color:#3498db;"> {{ $label }}
              </label>
              @endforeach
            </div>
          </div>

          {{-- Descripción --}}
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Descripción *</label>
            <div style="border:1.5px solid #e2e8f0; border-radius:7px; overflow:hidden;" id="modalMsgBox">
              <div style="background:#f7f9fc; border-bottom:1px solid #e2e8f0; padding:5px 10px; display:flex; gap:4px;">
                <button type="button" onclick="mfmt('bold')" class="fmt-btn" title="Negrita"><b>B</b></button>
                <button type="button" onclick="mfmt('italic')" class="fmt-btn" title="Cursiva"><i>I</i></button>
                <button type="button" onclick="mfmt('underline')" class="fmt-btn" title="Subrayado"><u>U</u></button>
                <div style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
                <button type="button" onclick="mfmt('insertUnorderedList')" class="fmt-btn"><i class="fas fa-list-ul"></i></button>
                <button type="button" onclick="mfmt('insertOrderedList')" class="fmt-btn"><i class="fas fa-list-ol"></i></button>
              </div>
              <div id="modalEditor" contenteditable="true"
                   style="min-height:140px; padding:12px; outline:none; font-size:0.86rem; color:#2d3748; line-height:1.6;"
                   oninput="syncModalEditor()"></div>
            </div>
            <textarea name="description" id="modalDescField" style="display:none;"></textarea>
          </div>

          {{-- Adjuntos --}}
          <div class="mb-1">
            <label class="form-label fw-semibold" style="font-size:0.85rem; color:#2d3748;">Adjuntos <small style="font-weight:400;color:#a0aec0;">(máx. 5 archivos, 5MB c/u)</small></label>
            <div style="border:2px dashed #e2e8f0; border-radius:8px; padding:14px; text-align:center; cursor:pointer; transition:border-color 0.2s;"
                 onclick="document.getElementById('modalAttach').click()"
                 onmouseenter="this.style.borderColor='#3498db'" onmouseleave="this.style.borderColor='#e2e8f0'">
              <i class="fas fa-paperclip" style="font-size:20px; color:#a0aec0;"></i>
              <div style="font-size:0.8rem; color:#a0aec0; margin-top:4px;">Haz clic para adjuntar</div>
              <div id="modalFileNames" style="font-size:0.79rem; color:#4a5568; margin-top:4px;"></div>
            </div>
            <input type="file" id="modalAttach" name="attachments[]" multiple style="display:none;"
                   onchange="document.getElementById('modalFileNames').textContent = Array.from(this.files).map(f=>f.name).join(', ')">
          </div>

        </form>
      </div>

      {{-- FOOTER --}}
      <div class="modal-footer" style="border-top:1px solid #f0f2f5; padding:14px 22px;">
        <button type="button" class="btn btn-sm" data-bs-dismiss="modal"
                style="color:#718096; background:none; border:1px solid #e2e8f0; border-radius:7px; padding:7px 18px;">Cancelar</button>
        <button type="button" onclick="submitModalTicket()"
                style="background:linear-gradient(135deg,#27ae60,#2ecc71); color:#fff; border:none; border-radius:7px; padding:8px 22px; font-weight:600; font-size:0.875rem; cursor:pointer;">
          <i class="fas fa-paper-plane me-1"></i> Enviar Ticket
        </button>
      </div>

    </div>
  </div>
</div>

@push('styles')
<style>
.modal-cat-label:has(input:checked) { border-color:#3498db; color:#2980b9; background:#ebf5fb; }
#modalMsgBox:focus-within { border-color:#3498db !important; box-shadow:0 0 0 3px rgba(52,152,219,0.12); }
.fmt-btn { background:none; border:1px solid transparent; padding:3px 8px; border-radius:4px; cursor:pointer; font-size:0.82rem; color:#4a5568; transition:all 0.15s; }
.fmt-btn:hover { background:#e2e8f0; border-color:#cbd5e0; }
</style>
@endpush

@section('scripts')
<script>
function filterTable(q) {
    const rows = document.querySelectorAll('#ticketTable tbody tr');
    q = q.toLowerCase();
    rows.forEach(r => {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
function mfmt(cmd) {
    document.getElementById('modalEditor').focus();
    document.execCommand(cmd, false, null);
    syncModalEditor();
}
function syncModalEditor() {
    document.getElementById('modalDescField').value = document.getElementById('modalEditor').innerHTML;
}
function submitModalTicket() {
    syncModalEditor();
    const text = document.getElementById('modalEditor').innerText.trim();
    if (!text) { document.getElementById('modalDescField').value = ''; }
    document.getElementById('modalTicketForm').submit();
}
// Limpiar modal al cerrar
document.getElementById('newTicketModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTicketForm').reset();
    document.getElementById('modalEditor').innerHTML = '';
    document.getElementById('modalDescField').value = '';
    document.getElementById('modalFileNames').textContent = '';
});
</script>
@endsection
