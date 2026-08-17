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
                <div class="item-left"><span class="item-icon"><i class="fas fa-id-card"></i></span>Mi Perfil</div>
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

        {{-- Info del usuario + foto de perfil --}}
        <div class="content-card" style="padding:24px;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">

                {{-- Avatar clickeable --}}
                <form id="avatarForm" method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp"
                           style="display:none;" onchange="this.closest('form').submit()">
                    <div onclick="document.getElementById('avatarInput').click()"
                         title="Cambiar foto de perfil"
                         style="width:72px;height:72px;border-radius:50%;flex-shrink:0;cursor:pointer;position:relative;overflow:hidden;
                                background:linear-gradient(135deg,#4f8cff,#2563eb);">
                        @if($user->avatar_url)
                            <img src="{{ Storage::url($user->avatar_url) }}"
                                 alt="Foto de perfil"
                                 style="width:100%;height:100%;object-fit:cover;">
                        @else
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.6rem;font-weight:700;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        {{-- Overlay hover --}}
                        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;
                                    opacity:0;transition:opacity .2s;"
                             onmouseenter="this.style.opacity=1" onmouseleave="this.style.opacity=0">
                            <i class="fas fa-camera" style="color:#fff;font-size:1.1rem;"></i>
                        </div>
                    </div>
                </form>

                <div>
                    <h2 style="margin:0 0 4px;font-size:1.1rem;color:#1a2332;">{{ $user->name }}</h2>
                    <p style="margin:0 0 2px;color:#718096;font-size:0.85rem;"><i class="fas fa-envelope me-1"></i>{{ $user->email }}</p>
                    @if($user->alternate_email)
                        <p style="margin:0 0 4px;color:#718096;font-size:0.82rem;"><i class="fas fa-envelope-open me-1" style="color:#f59e0b;"></i>{{ $user->alternate_email }} <span style="font-size:0.72rem;color:#a0aec0;">(alternativo)</span></p>
                    @endif
                    <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:0.73rem;font-weight:600;
                        background:{{ $user->isAdmin() ? "#fef3c7" : ($user->isSupport() ? "#dbeafe" : "#dcfce7") }};
                        color:{{ $user->isAdmin() ? "#92400e" : ($user->isSupport() ? "#1e40af" : "#166534") }};">
                        {{ $user->isAdmin() ? "Administrador" : ($user->isSupport() ? "Soporte" : "Usuario") }}
                    </span>
                    <div style="font-size:0.72rem;color:#a0aec0;margin-top:4px;"><i class="fas fa-camera me-1"></i>Haz clic en la foto para cambiarla</div>
                    @if(session('success_avatar'))
                    <div style="color:#1a7f43;font-size:0.78rem;margin-top:4px;"><i class="fas fa-check-circle me-1"></i>{{ session('success_avatar') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Información de Contacto ── --}}
        <div class="content-card" style="padding:24px;margin-bottom:20px;">
            <h3 style="margin:0 0 6px;font-size:1rem;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-envelope" style="color:#4f8cff;"></i> Información de Contacto
            </h3>
            <p style="margin:0 0 20px;font-size:0.82rem;color:#718096;">Puedes modificar tu correo principal y agregar un correo alternativo para recibir notificaciones si tu correo corporativo falla.</p>

            @if(session("success_profile"))
                <div style="background:#e6f7ed;border:1px solid #a7d7b3;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#1a7f43;font-size:0.85rem;display:flex;gap:8px;align-items:center;">
                    <i class="fas fa-check-circle"></i> {{ session("success_profile") }}
                </div>
            @endif

            <form method="POST" action="{{ route("profile.info") }}" style="max-width:480px;">
                @csrf

                {{-- Nombre --}}
                <div style="margin-bottom:16px;">
                    <label class="form-label-custom"><i class="fas fa-user me-1" style="color:#a0aec0;"></i> Nombre</label>
                    <input type="text" name="name"
                           class="form-control-custom @error("name") is-invalid @enderror"
                           value="{{ old("name", $user->name) }}" required>
                    @error("name")<div style="color:#e74c3c;font-size:0.78rem;margin-top:4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                </div>

                {{-- Email principal --}}
                <div style="margin-bottom:16px;">
                    <label class="form-label-custom"><i class="fas fa-envelope me-1" style="color:#a0aec0;"></i> Correo Principal</label>
                    <input type="email" name="email"
                           class="form-control-custom @error("email") is-invalid @enderror"
                           value="{{ old("email", $user->email) }}" required>
                    @error("email")<div style="color:#e74c3c;font-size:0.78rem;margin-top:4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                </div>

                {{-- Correo alternativo --}}
                <div style="margin-bottom:24px;">
                    <label class="form-label-custom">
                        <i class="fas fa-envelope-open me-1" style="color:#f59e0b;"></i>
                        Correo Alternativo <span style="font-weight:400;color:#a0aec0;">(opcional)</span>
                    </label>
                    <input type="email" name="alternate_email"
                           class="form-control-custom @error("alternate_email") is-invalid @enderror"
                           value="{{ old("alternate_email", $user->alternate_email) }}"
                           placeholder="Ej: tuemail@gmail.com">
                    <div style="font-size:0.75rem;color:#a0aec0;margin-top:5px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Si tu correo corporativo no está disponible, recibirás las notificaciones aquí.
                    </div>
                    @error("alternate_email")<div style="color:#e74c3c;font-size:0.78rem;margin-top:4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn-submit-ticket" style="display:inline-flex;align-items:center;gap:8px;width:auto;padding:10px 24px;">
                    <i class="fas fa-save"></i> Guardar Información
                </button>
            </form>
        </div>

        {{-- ── Cambio de contraseña ── --}}
        <div class="content-card" style="padding:24px;">
            <h3 style="margin:0 0 6px;font-size:1rem;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-lock" style="color:#4f8cff;"></i> Cambiar Contraseña
            </h3>
            <p style="margin:0 0 20px;font-size:0.82rem;color:#718096;">Por seguridad, ingresa tu contraseña actual para poder establecer una nueva.</p>

            @if(session("success_password"))
                <div style="background:#e6f7ed;border:1px solid #a7d7b3;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#1a7f43;font-size:0.85rem;display:flex;gap:8px;align-items:center;">
                    <i class="fas fa-check-circle"></i> {{ session("success_password") }}
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
                        <button type="button" onclick="toggleField('curPass','eyeCur')"
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
                        <button type="button" onclick="toggleField('newPass','eyeNew')"
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
                        <button type="button" onclick="toggleField('confPass','eyeConf')"
                                style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5a6a7d;cursor:pointer;padding:6px;font-size:0.95rem;">
                            <i class="fas fa-eye" id="eyeConf"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-submit-ticket" style="display:inline-flex;align-items:center;gap:8px;width:auto;padding:10px 24px;">
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
