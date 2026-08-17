@extends('layouts.app')

@section('title', 'Mis Tickets de Soporte')

@section('content')
<div class="page-wrapper tickets-index-page">

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
        @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-headset me-2"></i>Soporte</span>
            </div>
            <a href="{{ route('tickets.index') }}" class="sidebar-item {{ !request('agent_id') ? 'active' : '' }}">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-ticket-alt"></i></span>
                    Todos los Tickets
                </div>
                <span class="sidebar-badge">{{ $counts['total'] ?? 0 }}</span>
            </a>
            <a href="{{ route('tickets.index', ['agent_id' => Auth::id()]) }}" class="sidebar-item {{ request('agent_id') == Auth::id() ? 'active' : '' }}">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-user-check"></i></span>
                    Mis Tickets
                </div>
                @if($myTicketsCount > 0)
                    <span class="sidebar-badge badge-open">{{ $myTicketsCount }}</span>
                @endif
            </a>
            <a href="{{ route('tickets.my-stats') }}" class="sidebar-item">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-chart-bar"></i></span>
                    Mi Rendimiento
                </div>
            </a>
            <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#newTicketModal" onclick="this.blur()">
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
        @else
        {{-- Navegación para usuario común --}}
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-user me-2"></i>Mi Cuenta</span>
            </div>
            <a href="{{ route('tickets.index') }}" class="sidebar-item active">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-ticket-alt"></i></span>
                    Mis Tickets
                </div>
                <span class="sidebar-badge">{{ $counts['total'] ?? 0 }}</span>
            </a>
            {{-- El centro de ayuda va ANTES de "Nuevo Ticket" a proposito:
                 la idea es que el usuario vea la opcion de resolverlo solo
                 antes de decidir abrir un ticket. --}}
            <a href="{{ route('ayuda.index') }}" class="sidebar-item">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-book-open"></i></span>
                    Centro de Ayuda
                </div>
            </a>
            <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#newTicketModal" onclick="this.blur()">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-plus-circle"></i></span>
                    Nuevo Ticket
                </div>
            </a>
            <a href="{{ route('profile.index') }}" class="sidebar-item">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-user-cog"></i></span>
                    Mi Perfil
                </div>
            </a>
        </div>
        @endif

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

        {{-- ╔══════════════════════════════════════════════════════════════╗
             ║  BOTÓN CTA GRANDE — visible para todos los roles            ║
             ╚══════════════════════════════════════════════════════════════╝ --}}
        <a href="#" id="cta-new-ticket"
           data-bs-toggle="modal" data-bs-target="#newTicketModal"
           onclick="this.blur()" style="
            display:flex; align-items:center; gap:20px;
            background: linear-gradient(135deg, #1e3a5f 0%, #2980b9 50%, #3498db 100%);
            border-radius:14px; padding:22px 28px; margin-bottom:20px;
            text-decoration:none; box-shadow:0 6px 24px rgba(41,128,185,0.35);
            transition:transform .15s, box-shadow .15s; border:none;
            position:relative; overflow:hidden;">
            {{-- Decoración de fondo --}}
            <div style="position:absolute;right:-20px;top:-20px;width:120px;height:120px;
                background:rgba(255,255,255,0.05);border-radius:50%;pointer-events:none;"></div>
            <div style="position:absolute;right:40px;bottom:-30px;width:80px;height:80px;
                background:rgba(255,255,255,0.04);border-radius:50%;pointer-events:none;"></div>

            {{-- Icono --}}
            <div style="width:60px;height:60px;border-radius:14px;background:rgba(255,255,255,0.15);
                display:flex;align-items:center;justify-content:center;flex-shrink:0;
                box-shadow:0 2px 10px rgba(0,0,0,0.15);">
                <i class="fas fa-plus" style="color:#fff;font-size:1.6rem;"></i>
            </div>

            {{-- Texto --}}
            <div style="flex:1;">
                <div style="color:#fff;font-size:1.2rem;font-weight:800;margin-bottom:4px;letter-spacing:-0.2px;">
                    Crear Nuevo Ticket
                </div>
                <div style="color:rgba(255,255,255,0.8);font-size:0.84rem;font-weight:400;">
                    Reporta un problema técnico o solicita un servicio al equipo de TI
                </div>
            </div>

            {{-- Flecha --}}
            <div style="color:rgba(255,255,255,0.7);font-size:1.4rem;flex-shrink:0;">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>
        <script>
        document.getElementById('cta-new-ticket').addEventListener('mouseenter', function(){
            this.style.transform='translateY(-2px)';
            this.style.boxShadow='0 10px 32px rgba(41,128,185,0.45)';
        });
        document.getElementById('cta-new-ticket').addEventListener('mouseleave', function(){
            this.style.transform='translateY(0)';
            this.style.boxShadow='0 6px 24px rgba(41,128,185,0.35)';
        });
        </script>

        {{-- ╔══════════════════════════════════════════════════════════════╗
             ║  BANNER DE ALERTA — tickets resueltos o con respuesta       ║
             ╚══════════════════════════════════════════════════════════════╝ --}}
        @if(Auth::user()->isUser())
        @php
            $ticketsResueltos = $tickets->where('status', 'resolved')->count();
            $ticketsPendUser  = $tickets->where('status', 'pending_user')->count();
            $totalAtencion    = $ticketsResueltos + $ticketsPendUser;
        @endphp
        @if($totalAtencion > 0)
        <div id="attention-banner" style="
            display:flex; align-items:center; gap:14px;
            background:linear-gradient(90deg, #fff7ed, #fffbf0);
            border:2px solid #f59e0b; border-radius:12px;
            padding:16px 20px; margin-bottom:16px;
            box-shadow:0 3px 12px rgba(245,158,11,0.15);">
            <div style="width:42px;height:42px;border-radius:10px;background:#fef3c7;
                display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-bell" style="color:#f59e0b;font-size:1.15rem;animation:ring 2s ease-in-out infinite;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700;color:#92400e;font-size:0.9rem;margin-bottom:2px;">
                    Tienes {{ $totalAtencion }} ticket{{ $totalAtencion>1?'s':'' }} que requieren tu atención
                </div>
                <div style="font-size:0.8rem;color:#b45309;">
                    @if($ticketsResueltos > 0)
                        <i class="fas fa-check-circle me-1" style="color:#10b981;"></i>
                        {{ $ticketsResueltos }} resuelto{{ $ticketsResueltos>1?'s':'' }} esperando tu confirmación.
                    @endif
                    @if($ticketsPendUser > 0)
                        <i class="fas fa-hourglass-half me-1" style="color:#f59e0b;"></i>
                        {{ $ticketsPendUser }} esperando tu respuesta.
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-shrink:0;">
                @if($ticketsResueltos > 0)
                <a href="{{ route('tickets.index', ['status'=>'resolved']) }}"
                   style="padding:7px 14px;background:#10b981;color:#fff;border-radius:7px;font-size:0.8rem;font-weight:600;text-decoration:none;white-space:nowrap;">
                    Ver resueltos
                </a>
                @endif
                @if($ticketsPendUser > 0)
                <a href="{{ route('tickets.index', ['status'=>'pending_user']) }}"
                   style="padding:7px 14px;background:#f59e0b;color:#fff;border-radius:7px;font-size:0.8rem;font-weight:600;text-decoration:none;white-space:nowrap;">
                    Responder
                </a>
                @endif
            </div>
        </div>
        <style>
        @keyframes ring {
            0%,100%{transform:rotate(0)}
            10%{transform:rotate(-15deg)}
            20%{transform:rotate(12deg)}
            30%{transform:rotate(-8deg)}
            40%{transform:rotate(6deg)}
            50%{transform:rotate(0)}
        }
        </style>
        @endif
        @endif

        <div class="content-card">
            {{-- Barra de filtros (RF-RI-09) --}}
            <form method="GET" action="{{ route('tickets.index') }}" id="filterForm"
                  style="padding:12px 18px;background:#f7f9fc;border-bottom:1px solid #e8ecf0;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
                @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                <div style="flex:1;min-width:160px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Buscar</label>
                    <div style="position:relative;">
                        <i class="fas fa-search" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:.8rem;"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="N° ticket, título..."
                               style="width:100%;padding:7px 10px 7px 30px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                    </div>
                </div>
                <div style="min-width:120px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Prioridad</label>
                    <select name="priority" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                        <option value="">Todas</option>
                        <option value="low"      {{ request('priority')=='low'      ?'selected':'' }}>⚪ Baja</option>
                        <option value="medium"   {{ request('priority')=='medium'   ?'selected':'' }}>🔵 Media</option>
                        <option value="high"     {{ request('priority')=='high'     ?'selected':'' }}>🟠 Alta</option>
                        <option value="critical" {{ request('priority')=='critical' ?'selected':'' }}>🔴 Crítica</option>
                    </select>
                </div>
                {{-- RF-RI-09 / RF-ST-12: filtro por categoria --}}
                <div style="min-width:150px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Categoría</label>
                    <select name="categoria_id" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                        <option value="">Todas</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                                {{ $categoria->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- RF-ST-12: filtro por tecnico responsable (solo soporte/admin) --}}
                @if($supportUsers->isNotEmpty())
                <div style="min-width:150px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Técnico</label>
                    <select name="agent_id" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                        <option value="">Todos</option>
                        @foreach($supportUsers as $agente)
                            <option value="{{ $agente->id }}" {{ request('agent_id') == $agente->id ? 'selected' : '' }}>
                                {{ $agente->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- RF-ST-12: filtro por solicitante (solo soporte/admin) --}}
                @if($requesters->isNotEmpty())
                <div style="min-width:150px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Solicitante</label>
                    <select name="requester_id" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                        <option value="">Todos</option>
                        @foreach($requesters as $solicitante)
                            <option value="{{ $solicitante->id }}" {{ request('requester_id') == $solicitante->id ? 'selected' : '' }}>
                                {{ $solicitante->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div style="min-width:120px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Desde</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                </div>
                <div style="min-width:120px;">
                    <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Hasta</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                </div>
                <div style="display:flex;gap:6px;align-items:flex-end;">
                    <button type="submit" style="padding:7px 16px;background:#3498db;color:#fff;border:none;border-radius:6px;font-size:.83rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['search','priority','categoria_id','agent_id','requester_id','date_from','date_to']))
                    <a href="{{ route('tickets.index', request()->only('status')) }}" style="padding:7px 12px;background:#e2e8f0;color:#4a5568;border-radius:6px;font-size:.83rem;text-decoration:none;white-space:nowrap;">
                        <i class="fas fa-times me-1"></i> Limpiar
                    </a>
                    @endif
                </div>
                <span style="margin-left:auto;font-size:.78rem;color:#a0aec0;align-self:center;">
                    {{ $tickets->total() }} resultado{{ $tickets->total() != 1 ? 's' : '' }}
                </span>
            </form>

            <div class="content-card-header" style="border-top:none;padding-top:8px;">
                <span class="header-info">
                    Mostrando {{ $tickets->firstItem() ?? 0 }}–{{ $tickets->lastItem() ?? 0 }} de {{ $tickets->total() ?? 0 }}
                </span>
            </div>

            @if($tickets->count() > 0)
            <div style="overflow-x:auto;">
                <table class="ticket-table" id="ticketTable">
                    @php
                        // Ordenamiento de columnas: se conservan los filtros activos y
                        // se vuelve a la pagina 1 al cambiar el orden.
                        $ordenActual = request('sort');
                        $dirActual   = request('dir') === 'asc' ? 'asc' : 'desc';

                        // Si ya se ordena por esa columna, el clic invierte la direccion.
                        $enlaceOrden = fn ($clave) => request()->fullUrlWithQuery([
                            'sort' => $clave,
                            'dir'  => ($ordenActual === $clave && $dirActual === 'asc') ? 'desc' : 'asc',
                            'page' => null,
                        ]);

                        $iconoOrden = fn ($clave) => $ordenActual === $clave
                            ? ($dirActual === 'asc' ? '▲' : '▼')
                            : '⇅';

                        $estiloOrden = 'color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:5px;cursor:pointer;';
                    @endphp

                    <thead>
                        <tr>
                            <th>
                                <a href="{{ $enlaceOrden('departamento') }}" style="{{ $estiloOrden }}" title="Ordenar por departamento">
                                    Departamento <span class="sort-icon">{{ $iconoOrden('departamento') }}</span>
                                </a>
                            </th>
                            <th>
                                <a href="{{ $enlaceOrden('asunto') }}" style="{{ $estiloOrden }}" title="Ordenar por asunto">
                                    Asunto <span class="sort-icon">{{ $iconoOrden('asunto') }}</span>
                                </a>
                            </th>
                            <th>
                                <a href="{{ $enlaceOrden('estado') }}" style="{{ $estiloOrden }}" title="Ordenar por estado">
                                    Estado <span class="sort-icon">{{ $iconoOrden('estado') }}</span>
                                </a>
                            </th>
                            <th>Prioridad</th>
                            <th>SLA</th>
                            <th>Asignado</th>
                            <th>
                                <a href="{{ $enlaceOrden('actualizado') }}" style="{{ $estiloOrden }}" title="Ordenar por fecha de última actualización">
                                    Actualizado <span class="sort-icon">{{ $iconoOrden('actualizado') }}</span>
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                        <tr onclick="window.location='{{ route('tickets.show', $ticket) }}'" style="cursor:pointer;">
                            <td class="ticket-dept" data-label="Departamento">{{ $ticket->department->name ?? 'N/A' }}</td>
                            <td class="celda-asunto">
                                <a href="{{ route('tickets.show', $ticket) }}" class="ticket-subject-link" onclick="event.stopPropagation();">
                                    #{{ $ticket->ticket_number }}
                                </a>
                                <div class="ticket-subject-sub">{{ Str::limit($ticket->title, 55) }}</div>
                            </td>
                            <td data-label="Estado">
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
                            <td data-label="Prioridad">
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
                            <td>
                                {{-- RN-17 / RF-ST-11: semaforo de cumplimiento del SLA de resolucion.
                                     Solo se muestra en tickets activos: uno cerrado ya no corre riesgo. --}}
                                @php
                                    $finalizado = in_array($ticket->status, ['resolved', 'closed']);
                                    $slaEstado  = $finalizado ? null : $ticket->getSlaResolutionStatus();
                                @endphp
                                @component('components.sla-badge', ['status' => $slaEstado])@endcomponent
                            </td>
                            <td class="ticket-assigned-cell" data-label="Asignado">
                                {{ $ticket->assignedTo->name ?? '—' }}
                            </td>
                            <td class="ticket-updated-cell" data-label="Actualizado">
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
                <button type="button" data-bs-toggle="modal" data-bs-target="#newTicketModal"
                        class="btn-submit-ticket" style="border:none;cursor:pointer;margin-top:8px;">
                    <i class="fas fa-plus me-1"></i> Abrir primer ticket
                </button>
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

/* Ajuste puntual de ancho para la vista de listado de tickets */
.tickets-index-page {
    width: calc(100vw - 12px);
    max-width: none;
    margin: 0;
    padding: 16px 8px 32px;
    gap: 14px;
}

.tickets-index-page .sidebar {
    width: 200px;
}

.tickets-index-page .main-content {
    min-width: 0;
    width: calc(100% - 214px);
}

.tickets-index-page .content-card {
    overflow: visible;
}

.tickets-index-page .ticket-table {
    width: 100%;
    min-width: 0;
    table-layout: fixed;
}

.tickets-index-page .ticket-table th,
.tickets-index-page .ticket-table td {
    padding-left: 8px;
    padding-right: 8px;
}

.tickets-index-page .ticket-table th:nth-child(1),
.tickets-index-page .ticket-table td:nth-child(1) { width: 17%; }
.tickets-index-page .ticket-table th:nth-child(2),
.tickets-index-page .ticket-table td:nth-child(2) { width: 19%; }
.tickets-index-page .ticket-table th:nth-child(3),
.tickets-index-page .ticket-table td:nth-child(3) { width: 12%; }
.tickets-index-page .ticket-table th:nth-child(4),
.tickets-index-page .ticket-table td:nth-child(4) { width: 10%; }
.tickets-index-page .ticket-table th:nth-child(5),
.tickets-index-page .ticket-table td:nth-child(5) { width: 9%; }
.tickets-index-page .ticket-table th:nth-child(6),
.tickets-index-page .ticket-table td:nth-child(6) { width: 12%; }
.tickets-index-page .ticket-table th:nth-child(7),
.tickets-index-page .ticket-table td:nth-child(7) { width: 21%; }

.tickets-index-page .ticket-table th {
    white-space: normal;
    line-height: 1.2;
}

.tickets-index-page .ticket-dept,
.tickets-index-page .ticket-subject-sub,
.tickets-index-page .ticket-assigned-cell,
.tickets-index-page .ticket-updated-cell {
    word-break: break-word;
}

.tickets-index-page .ticket-assigned-cell,
.tickets-index-page .ticket-updated-cell {
    font-size: 0.8rem;
    color: #718096;
}

.tickets-index-page .ticket-table th,
.tickets-index-page .ticket-table td {
    padding-left: 10px;
    padding-right: 10px;
}

@media (max-width: 1200px) {
    .tickets-index-page {
        width: 100%;
        padding-left: 10px;
        padding-right: 10px;
    }

    .tickets-index-page .main-content {
        width: 100%;
    }
}
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

// ── AJAX: cargar subcategorías del catálogo ──────────────────────
function loadModalSubcats(catId) {
    const subSel  = document.getElementById('modalSubcatSelect');
    const tipoSel = document.getElementById('modalTipoSelect');

    // Resetear tipo
    tipoSel.innerHTML = '<option value="">Seleccionar tipo (opcional)...</option>';
    tipoSel.disabled = true;

    if (!catId) {
        subSel.innerHTML = '<option value="">Primero selecciona categoría...</option>';
        subSel.disabled = true;
        return;
    }

    subSel.innerHTML = '<option value="">Cargando...</option>';
    subSel.disabled = true;

    fetch(`/api/categorias/${catId}/subcategorias`)
        .then(r => r.json())
        .then(data => {
            subSel.innerHTML = '<option value="">Seleccionar subcategoría...</option>';
            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                subSel.appendChild(opt);
            });
            subSel.disabled = false;
        })
        .catch(() => {
            subSel.innerHTML = '<option value="">Error al cargar</option>';
        });
}

// ── AJAX: cargar tipos de incidente ──────────────────────────────
function loadModalTipos(subcatId) {
    const tipoSel = document.getElementById('modalTipoSelect');

    if (!subcatId) {
        tipoSel.innerHTML = '<option value="">Seleccionar tipo (opcional)...</option>';
        tipoSel.disabled = true;
        return;
    }

    tipoSel.innerHTML = '<option value="">Cargando...</option>';
    tipoSel.disabled = true;

    fetch(`/api/subcategorias/${subcatId}/tipos`)
        .then(r => r.json())
        .then(data => {
            tipoSel.innerHTML = '<option value="">Sin tipo específico</option>';
            data.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                tipoSel.appendChild(opt);
            });
            tipoSel.disabled = (data.length === 0);
        })
        .catch(() => {
            tipoSel.innerHTML = '<option value="">Sin tipos</option>';
        });
}

// ── Reset modal al cerrar ────────────────────────────────────────
document.getElementById('newTicketModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTicketForm').reset();
    document.getElementById('modalEditor').innerHTML = '';
    document.getElementById('modalDescField').value = '';
    document.getElementById('modalFileNames').textContent = '';
    // Resetear selectores de clasificación
    const subSel  = document.getElementById('modalSubcatSelect');
    const tipoSel = document.getElementById('modalTipoSelect');
    subSel.innerHTML  = '<option value="">Primero selecciona categoría...</option>';
    subSel.disabled   = true;
    tipoSel.innerHTML = '<option value="">Seleccionar tipo (opcional)...</option>';
    tipoSel.disabled  = true;
});
</script>
@endsection

{{-- Modal simplificado de nuevo ticket (Reunión 4) --}}
@include('partials.create_ticket_modal')

