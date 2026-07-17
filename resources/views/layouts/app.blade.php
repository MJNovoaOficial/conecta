<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Conecta - Mesa de Ayuda')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            margin: 0;
        }

        /* ── TOP NAVBAR ── */
        .top-navbar {
            background: linear-gradient(90deg, #1a2332 0%, #243447 100%);
            padding: 0 24px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
        }

        .brand .brand-icon {
            background: linear-gradient(135deg, #3498db, #2ecc71);
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 2px 6px rgba(52,152,219,0.4);
        }

        .brand span strong { color: #3498db; }

        .top-nav-items {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .top-nav-items a {
            color: #cbd5e0;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .top-nav-items a:hover,
        .top-nav-items a.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .top-nav-items a.btn-open-ticket {
            background: #27ae60;
            color: #fff;
            font-weight: 600;
            padding: 6px 14px;
        }

        .top-nav-items a.btn-open-ticket:hover {
            background: #219a52;
        }

        .nav-divider { width: 1px; height: 24px; background: rgba(255,255,255,0.15); margin: 0 6px; }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #cbd5e0;
            font-size: 0.85rem;
        }

        .nav-user-name {
            color: #fff;
            font-weight: 600;
        }

        .nav-user-sep {
            width: 1px;
            height: 14px;
            background: rgba(255,255,255,0.25);
            display: inline-block;
            vertical-align: middle;
        }

        .nav-user-role {
            color: #a0aec0;
            font-size: 0.78rem;
        }

        .user-menu form button {
            background: rgba(231,76,60,0.15);
            border: 1px solid rgba(231,76,60,0.3);
            color: #fc8181;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-menu form button:hover {
            background: rgba(231,76,60,0.3);
            color: #fff;
        }

        /* ── TOAST NOTIFICATIONS ── */
        .toast-container {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .toast-msg {
            pointer-events: all;
            min-width: 280px;
            max-width: 380px;
            padding: 13px 16px 10px;
            border-radius: 10px;
            font-size: 0.865rem;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.14);
            animation: toastIn .28s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
        }
        .toast-msg.toast-success { background: #fff; border-left: 4px solid #27ae60; color: #1a2332; }
        .toast-msg.toast-error   { background: #fff; border-left: 4px solid #e74c3c; color: #1a2332; }
        .toast-icon-s { color: #27ae60; flex-shrink: 0; margin-top: 1px; }
        .toast-icon-e { color: #e74c3c; flex-shrink: 0; margin-top: 1px; }
        .toast-progress {
            position: absolute;
            bottom: 0; left: 0;
            height: 3px;
            width: 100%;
            background: rgba(0,0,0,0.06);
        }
        .toast-bar-success { background: #27ae60; height: 100%; width: 100%; transform-origin: left; }
        .toast-bar-error   { background: #e74c3c; height: 100%; width: 100%; transform-origin: left; }
        @keyframes toastIn {
            from { opacity:0; transform: translateY(18px) scale(.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        /* ── PAGE WRAPPER ── */
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 24px 40px;
            display: flex;
            gap: 22px;
            align-items: flex-start;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 230px;
            flex-shrink: 0;
        }

        .sidebar-section {
            background: #fff;
            border-radius: 10px;
            margin-bottom: 14px;
            border: 1px solid #e8ecf0;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }

        .sidebar-section-header {
            background: #2d3748;
            color: #a0aec0;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-section-header .toggle-icon {
            cursor: pointer;
            opacity: 0.6;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 14px;
            font-size: 0.84rem;
            color: #4a5568;
            text-decoration: none;
            border-bottom: 1px solid #f0f2f5;
            transition: all 0.15s;
        }

        .sidebar-item:last-child { border-bottom: none; }

        .sidebar-item:hover {
            background: #f7f9fc;
            color: #2c3e50;
        }

        .sidebar-item.active {
            background: #ebf5fb;
            color: #2980b9;
            font-weight: 600;
            border-left: 3px solid #2980b9;
        }

        .sidebar-item .item-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-item .item-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            border: 2px solid #cbd5e0;
        }

        .sidebar-item.active .item-dot { border-color: #2980b9; background: #2980b9; }

        .sidebar-badge {
            background: #e2e8f0;
            color: #718096;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 22px;
            text-align: center;
        }

        .sidebar-badge.badge-open { background: #c6f6d5; color: #276749; }
        .sidebar-badge.badge-pending { background: #fef3c7; color: #92400e; }

        .sidebar-item .item-icon {
            width: 16px;
            text-align: center;
            color: #a0aec0;
            font-size: 0.78rem;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            flex: 1;
            min-width: 0;
        }

        .page-header {
            margin-bottom: 18px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a2332;
            margin: 0 0 4px;
        }

        .page-header .sub-title {
            color: #718096;
            font-size: 0.9rem;
        }

        .breadcrumb-bar {
            font-size: 0.8rem;
            color: #a0aec0;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breadcrumb-bar a { color: #3498db; text-decoration: none; }
        .breadcrumb-bar a:hover { text-decoration: underline; }

        /* ── CONTENT CARD ── */
        .content-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e8ecf0;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .content-card-header {
            padding: 12px 18px;
            background: #f7f9fc;
            border-bottom: 1px solid #e8ecf0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .content-card-header .header-info {
            color: #718096;
            font-size: 0.82rem;
        }

        .content-card-search {
            position: relative;
        }

        .content-card-search input {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 10px 5px 30px;
            font-size: 0.82rem;
            color: #4a5568;
            width: 180px;
            outline: none;
            transition: border-color 0.2s;
        }

        .content-card-search input:focus { border-color: #3498db; }

        .content-card-search .search-icon {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 0.75rem;
        }

        /* ── TICKET TABLE ── */
        .ticket-table { width: 100%; border-collapse: collapse; }

        .ticket-table thead tr {
            border-bottom: 2px solid #e2e8f0;
        }

        .ticket-table th {
            padding: 10px 14px;
            font-size: 0.78rem;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: #f7f9fc;
            white-space: nowrap;
        }

        .ticket-table th .sort-icon {
            margin-left: 4px;
            opacity: 0.4;
            font-size: 0.7rem;
        }

        .ticket-table tbody tr {
            border-bottom: 1px solid #f0f2f5;
            transition: background 0.15s;
            cursor: pointer;
        }

        .ticket-table tbody tr:hover { background: #f7faff; }
        .ticket-table tbody tr:last-child { border-bottom: none; }

        .ticket-table td {
            padding: 12px 14px;
            font-size: 0.85rem;
            color: #4a5568;
            vertical-align: middle;
        }

        .ticket-dept {
            color: #2d3748;
            font-weight: 500;
            font-size: 0.84rem;
        }

        .ticket-subject-link {
            color: #2980b9;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .ticket-subject-link:hover { text-decoration: underline; }

        .ticket-subject-sub {
            color: #718096;
            font-size: 0.78rem;
            margin-top: 2px;
        }

        /* STATUS BADGES */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.76rem;
            font-weight: 600;
            border: 1.5px solid;
            white-space: nowrap;
        }

        .status-open       { color: #276749; border-color: #276749; background: #f0fff4; }
        .status-closed     { color: #718096; border-color: #cbd5e0; background: #f7f9fc; }
        .status-pending    { color: #744210; border-color: #d69e2e; background: #fffbeb; }
        .status-in-progress { color: #1a365d; border-color: #3498db; background: #ebf8ff; }
        .status-resolved   { color: #1e4e3e; border-color: #38a169; background: #f0fff4; }
        .status-forwarded  { color: #1a365d; border-color: #3182ce; background: #ebf8ff; }

        /* PRIORITY BADGES */
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .priority-low      { background: #e2e8f0; color: #718096; }
        .priority-medium   { background: #fef3c7; color: #92400e; }
        .priority-high     { background: #fed7d7; color: #9b2335; }
        .priority-critical { background: #fde8e8; color: #742a2a; }

        /* ── FORM STYLES ── */
        .form-section {
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .form-section-header {
            background: #f7f9fc;
            padding: 10px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-section-body { padding: 18px; }

        .form-label-custom {
            font-size: 0.82rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 5px;
            display: block;
        }

        .form-control-custom {
            border: 1.5px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.85rem;
            width: 100%;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            color: #2d3748;
            background: #fff;
        }

        .form-control-custom:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
        }

        .form-control-custom.is-invalid { border-color: #e74c3c; }

        .btn-submit-ticket {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: #fff;
            border: none;
            padding: 10px 28px;
            border-radius: 7px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 8px rgba(39,174,96,0.3);
        }

        .btn-submit-ticket:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(39,174,96,0.4);
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state .empty-icon {
            font-size: 48px;
            color: #cbd5e0;
            margin-bottom: 16px;
        }

        .empty-state h5 { color: #4a5568; font-weight: 600; }
        .empty-state p { color: #a0aec0; font-size: 0.875rem; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .page-wrapper { flex-direction: column; padding: 12px; }
            .sidebar { width: 100%; }
            .top-nav-items { display: none; }
        }
    </style>
    @yield('styles')
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="top-navbar">
    <a href="{{ auth()->check() ? route('tickets.index') : route('home') }}" class="brand">
        <img src="{{ asset('images/logo.png') }}" alt="Dimak" style="height:36px; width:auto; object-fit:contain;">
    </a>

    @auth
    <div class="user-menu">
        <div class="nav-user-info">
            <span class="nav-user-name">{{ Str::limit(Auth::user()->name, 22) }}</span>
            <span class="nav-user-sep"></span>
            <span class="nav-user-role">{{ Auth::user()->role === 'admin' ? 'Administrador' : (Auth::user()->role === 'support' ? 'Soporte' : 'Usuario') }}</span>
        </div>

        {{-- Campana de notificaciones --}}
        <div class="notif-bell-wrap" id="notifWrap" style="position:relative;">
            <button id="notifBtn" onclick="toggleNotifPanel()" style="background:none;border:none;cursor:pointer;padding:6px;position:relative;color:#cbd5e0;" title="Notificaciones">
                <i class="fas fa-bell" style="font-size:1.1rem;"></i>
                <span id="notifBadge" style="display:none;position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border-radius:99px;font-size:.6rem;font-weight:700;min-width:14px;height:14px;line-height:14px;text-align:center;padding:0 2px;"></span>
            </button>
            <div id="notifPanel" style="display:none;position:absolute;right:0;top:calc(100% + 6px);width:320px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.15);z-index:2000;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-bottom:1px solid #f0f2f5;">
                    <span style="font-size:.85rem;font-weight:700;color:#1a2332;">Notificaciones</span>
                    <form method="POST" action="{{ route('notifications.readAll') }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#3498db;padding:0;">Marcar todas leídas</button>
                    </form>
                </div>
                <div id="notifList" style="max-height:340px;overflow-y:auto;">
                    <div style="text-align:center;padding:1.5rem;color:#a0aec0;font-size:.83rem;">
                        <i class="fas fa-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:.5rem;"></i>
                        Cargando notificaciones…
                    </div>
                </div>
                <div style="padding:.5rem 1rem;border-top:1px solid #f0f2f5;text-align:center;">
                    <a href="{{ route('notifications.index') }}" style="font-size:.8rem;color:#3498db;text-decoration:none;">Ver todas las notificaciones</a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit"><i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión</button>
        </form>
    </div>
    @else
    @if(!request()->routeIs('home', 'register', 'tickets.guest.create', 'tickets.guest.show'))
    <div class="top-nav-items">
        <a href="{{ route('login') }}"><i class="fas fa-sign-in-alt"></i> Iniciar Sesión</a>
        <a href="{{ route('register') }}"><i class="fas fa-user-plus"></i> Registrarse</a>
    </div>
    @endif
    <div class="user-menu"></div>
    @endauth
</nav>

<!-- TOAST NOTIFICATIONS -->
<div class="toast-container" id="toastContainer">
    @if(session('success'))
    <div class="toast-msg toast-success auto-toast" data-timeout="4000">
        <i class="fas fa-check-circle toast-icon-s"></i>
        <span>{{ session('success') }}</span>
        <div class="toast-progress"><div class="toast-bar-success" id="toastBarS"></div></div>
    </div>
    @endif
    @if(session('error'))
    <div class="toast-msg toast-error auto-toast" data-timeout="5000">
        <i class="fas fa-exclamation-circle toast-icon-e"></i>
        <span>{{ session('error') }}</span>
        <div class="toast-progress"><div class="toast-bar-error" id="toastBarE"></div></div>
    </div>
    @endif
    @if($errors->any())
    <div class="toast-msg toast-error auto-toast" data-timeout="6000">
        <i class="fas fa-exclamation-circle toast-icon-e"></i>
        <span>{{ $errors->first() }}</span>
        <div class="toast-progress"><div class="toast-bar-error"></div></div>
    </div>
    @endif
</div>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.auto-toast').forEach(function (el) {
        var timeout = parseInt(el.dataset.timeout) || 4000;
        var bar = el.querySelector('[class*="toast-bar"]');
        if (bar) {
            bar.style.transition = 'transform ' + timeout + 'ms linear';
            setTimeout(function() { bar.style.transform = 'scaleX(0)'; }, 30);
        }
        setTimeout(function () {
            el.style.transition = 'opacity .3s, transform .3s';
            el.style.opacity = '0';
            el.style.transform = 'translateY(12px)';
            setTimeout(function () { el.remove(); }, 320);
        }, timeout);
    });
});

// ── NOTIFICACIONES IN-APP ─────────────────────────────────
@auth
(function() {
    var panelOpen = false;

    function updateBadge() {
        fetch('{{ route("notifications.count") }}', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                var badge = document.getElementById('notifBadge');
                if (!badge) return;
                if (data.count > 0) {
                    badge.textContent = data.count > 99 ? '99+' : data.count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }).catch(() => {});
    }

    function loadNotifications() {
        var list = document.getElementById('notifList');
        if (!list) return;
        fetch('{{ route("notifications.recent") }}', {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then(r => r.json())
            .then(data => {
                if (!data.items || data.items.length === 0) {
                    list.innerHTML = '<div style="text-align:center;padding:1.5rem;color:#a0aec0;font-size:.83rem;"><i class="fas fa-check-circle" style="font-size:1.5rem;display:block;margin-bottom:.5rem;color:#68d391;"></i>Sin notificaciones pendientes</div>';
                    return;
                }
                var icons = {new_ticket:'🎫', assigned:'👤', comment:'💬', forwarded:'↗️', closed:'🔒', default:'🔔'};
                list.innerHTML = data.items.map(n => `
                    <a href="${n.url}" style="display:block;padding:.65rem 1rem;border-bottom:1px solid #f0f2f5;text-decoration:none;background:${n.read?'#fff':'#f0f7ff'};transition:background .15s;" onmouseover="this.style.background='#f7faff'" onmouseout="this.style.background='${n.read?'#fff':'#f0f7ff'}'">
                        <div style="display:flex;align-items:flex-start;gap:.5rem;">
                            <span style="font-size:1rem;margin-top:1px;">${icons[n.type] || icons.default}</span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.82rem;font-weight:${n.read?500:700};color:#1a2332;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${n.title}</div>
                                <div style="font-size:.76rem;color:#718096;margin-top:1px;">${n.body || ''}</div>
                                <div style="font-size:.72rem;color:#a0aec0;margin-top:2px;">${n.time}</div>
                            </div>
                            ${!n.read ? '<span style="width:7px;height:7px;background:#3498db;border-radius:50%;flex-shrink:0;margin-top:5px;"></span>' : ''}
                        </div>
                    </a>`).join('');
            }).catch(() => {});
    }

    window.toggleNotifPanel = function() {
        var panel = document.getElementById('notifPanel');
        if (!panel) return;
        panelOpen = !panelOpen;
        panel.style.display = panelOpen ? 'block' : 'none';
        if (panelOpen) loadNotifications();
    };

    document.addEventListener('click', function(e) {
        var wrap = document.getElementById('notifWrap');
        if (wrap && !wrap.contains(e.target) && panelOpen) {
            document.getElementById('notifPanel').style.display = 'none';
            panelOpen = false;
        }
    });

    // Actualizar badge cada 60 segundos
    updateBadge();
    setInterval(updateBadge, 60000);
})();
@endauth
</script>
</body>
</html>
