@extends("layouts.app")
@section("title", "Mi Perfil - Conecta")
@section("content")
<div class="page-wrapper">
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-user me-2"></i>Mi Cuenta</span>
            </div>
            <a href="{{ route("profile.index") }}" class="sidebar-item active">
                <div class="item-left"><span class="item-icon"><i class="fas fa-lock"></i></span>Cambiar Contraseña</div>
            </a>
            <a href="{{ route("tickets.index") }}" class="sidebar-item">
                <div class="item-left"><span class="item-icon"><i class="fas fa-ticket-alt"></i></span>Mis Tickets</div>
            </a>
        </div>
    </aside>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-user-circle" style="color:#4f8cff;margin-right:8px;"></i>Mi Perfil</h1>
            <div class="breadcrumb-bar">
                <a href="{{ route("home") }}">Inicio</a>
                <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                <span>Mi Perfil</span>
            </div>
        </div>

        {{-- Info del usuario --}}
        <div class="content-card" style="padding:24px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
                <div style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#4f8cff,#2563eb);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:700;flex-shrink:0;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 style="margin:0 0 4px;font-size:1.1rem;color:#1a2332;">{{ $user->name }}</h2>
                    <p style="margin:0 0 4px;color:#718096;font-size:0.85rem;"><i class="fas fa-envelope me-1"></i>{{ $user->email }}</p>
                    <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:0.73rem;font-weight:600;
                        background:{{ $user->isAdmin() ? "#fef3c7" : ($user->isSupport() ? "#dbeafe" : "#dcfce7") }};
                        color:{{ $user->isAdmin() ? "#92400e" : ($user->isSupport() ? "#1e40af" : "#166534") }};">
                        {{ $user->isAdmin() ? "Administrador" : ($user->isSupport() ? "Soporte" : "Usuario") }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Cambio de contraseña --}}
        <div class="content-card" style="padding:24px;">
            <h3 style="margin:0 0 20px;font-size:1rem;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-lock" style="color:#4f8cff;"></i> Cambiar Contraseña
            </h3>

            @if(session("success"))
                <div style="background:#e6f7ed;border:1px solid #a7d7b3;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#1a7f43;font-size:0.85rem;display:flex;gap:8px;align-items:center;">
                    <i class="fas fa-check-circle"></i> {{ session("success") }}
                </div>
            @endif

            <form method="POST" action="{{ route("profile.password") }}" style="max-width:440px;">
                @csrf
                <div style="margin-bottom:16px;">
                    <label class="form-label-custom">Contraseña Actual</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:0.82rem;"><i class="fas fa-lock"></i></span>
                        <input type="password" name="current_password" id="curPass"
                               class="form-control-custom @error("current_password") is-invalid @enderror"
                               style="padding-left:32px;padding-right:38px;" placeholder="Tu contraseña actual" required>
                        <button type="button" onclick="toggleField("curPass","eyeCur")"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5a6a7d;cursor:pointer;padding:6px;font-size:0.95rem;">
                            <i class="fas fa-eye" id="eyeCur"></i>
                        </button>
                    </div>
                    @error("current_password")
                        <div style="color:#e74c3c;font-size:0.78rem;margin-top:4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label-custom">Nueva Contraseña</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:0.82rem;"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="newPass"
                               class="form-control-custom @error("password") is-invalid @enderror"
                               style="padding-left:32px;padding-right:38px;" placeholder="Mínimo 8 caracteres" required minlength="8">
                        <button type="button" onclick="toggleField("newPass","eyeNew")"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5a6a7d;cursor:pointer;padding:6px;font-size:0.95rem;">
                            <i class="fas fa-eye" id="eyeNew"></i>
                        </button>
                    </div>
                    @error("password")
                        <div style="color:#e74c3c;font-size:0.78rem;margin-top:4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                </div>
                <div style="margin-bottom:24px;">
                    <label class="form-label-custom">Confirmar Nueva Contraseña</label>
                    <div style="position:relative;">
                        <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:0.82rem;"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password_confirmation" id="confPass"
                               class="form-control-custom" style="padding-left:32px;padding-right:38px;"
                               placeholder="Repite la nueva contraseña" required minlength="8">
                        <button type="button" onclick="toggleField("confPass","eyeConf")"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5a6a7d;cursor:pointer;padding:6px;font-size:0.95rem;">
                            <i class="fas fa-eye" id="eyeConf"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit-ticket" style="display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-save"></i> Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
@section("scripts")
<script>
function toggleField(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}
</script>
@endsection
