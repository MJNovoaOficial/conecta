{{-- Admin Sidebar — se incluye en las páginas de administración --}}
@php
    $sidebarActive = $active ?? '';
    $navItems = [
        ['route' => 'admin.dashboard', 'icon' => 'fas fa-chart-line', 'label' => 'Dashboard', 'key' => 'dashboard'],
        ['route' => 'tickets.index', 'icon' => 'fas fa-ticket-alt', 'label' => 'Todos los Tickets', 'key' => 'tickets'],
        ['route' => 'admin.reports.index', 'icon' => 'fas fa-file-chart-bar', 'label' => 'Reportes', 'key' => 'reports'],
        ['route' => 'admin.reports.agents', 'icon' => 'fas fa-user-chart', 'label' => 'KPIs por Agente', 'key' => 'agents'],
        ['route' => 'admin.priority-rules.index', 'icon' => 'fas fa-sliders-h', 'label' => 'Reglas de Prioridad', 'key' => 'priority_rules'],
        ['route' => 'admin.categories.index', 'icon' => 'fas fa-tags', 'label' => 'Categorías', 'key' => 'categories'],
        ['route' => 'admin.sla.index', 'icon' => 'fas fa-clock', 'label' => 'Configurar SLA', 'key' => 'sla'],
        ['route' => 'admin.departments.index', 'icon' => 'fas fa-building', 'label' => 'Departamentos', 'key' => 'departments'],
        ['route' => 'admin.users.index', 'icon' => 'fas fa-users', 'label' => 'Usuarios', 'key' => 'users'],
        ['route' => 'admin.settings.index', 'icon' => 'fas fa-cog', 'label' => 'Configuración', 'key' => 'settings'],
        ['route' => 'admin.audit.index', 'icon' => 'fas fa-shield-alt', 'label' => 'Auditoría', 'key' => 'audit'],
    ];
@endphp

<style>
.admin-sidebar {
    width: 220px;
    min-width: 220px;
    background: #1a2332;
    min-height: calc(100vh - 60px);
    display: flex;
    flex-direction: column;
    padding: 20px 0 28px;
    position: sticky;
    top: 60px;
    height: calc(100vh - 60px);
    overflow-y: auto;
}
.sidebar-section-title {
    font-size: .65rem;
    font-weight: 700;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: .1em;
    padding: 16px 18px 6px;
}
.sidebar-nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 18px;
    font-size: .82rem;
    color: #a0aec0;
    text-decoration: none;
    border-radius: 0;
    transition: background .15s, color .15s;
    position: relative;
    white-space: nowrap;
}
.sidebar-nav-item:hover {
    background: rgba(255,255,255,.06);
    color: #fff;
    text-decoration: none;
}
.sidebar-nav-item.active {
    background: rgba(79,140,255,.18);
    color: #60a5fa;
    font-weight: 600;
}
.sidebar-nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #4f8cff;
    border-radius: 0 2px 2px 0;
}
.sidebar-nav-item i {
    width: 16px;
    text-align: center;
    font-size: .8rem;
    flex-shrink: 0;
}
.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,.08);
    margin: 10px 16px;
}
@media(max-width:768px) {
    .admin-sidebar { display: none; }
}
</style>

<aside class="admin-sidebar">
    <div class="sidebar-section-title">Panel Admin</div>

    @foreach($navItems as $item)
        @if($item['key'] === 'reports')
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">Reportes</div>
        @elseif($item['key'] === 'priority_rules')
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">Configuración</div>
        @elseif($item['key'] === 'users')
            <div class="sidebar-divider"></div>
            <div class="sidebar-section-title">Gestión</div>
        @endif

        @if(\Route::has($item['route']))
        <a href="{{ route($item['route']) }}"
           class="sidebar-nav-item {{ $sidebarActive === $item['key'] ? 'active' : '' }}">
            <i class="{{ $item['icon'] }}"></i>
            {{ $item['label'] }}
        </a>
        @endif
    @endforeach
</aside>
