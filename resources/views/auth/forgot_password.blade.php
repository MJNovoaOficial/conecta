@extends("layouts.app")
@section("title", "Recuperar Contraseña - Conecta")
@section("content")
<div style="min-height:calc(100vh - 52px);display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:28px;">
            <h1 style="font-size:1.4rem;font-weight:700;color:#1a2332;margin:0 0 4px;">Recuperar Contraseña</h1>
            <p style="color:#718096;font-size:0.85rem;margin:0;">Te enviaremos un enlace a tu correo</p>
        </div>
        <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;">
            <div style="background:linear-gradient(90deg,#1a2332,#243447);padding:14px 22px;">
                <h2 style="color:#fff;font-size:0.95rem;font-weight:600;margin:0;">
                    <i class="fas fa-key me-2" style="color:#f59e0b;"></i>Restablecer acceso
                </h2>
            </div>
            <div style="padding:24px 22px;">
                @if(session("status"))
                    <div style="background:#e6f7ed;border:1px solid #a7d7b3;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#1a7f43;font-size:0.85rem;display:flex;gap:8px;align-items:center;">
                        <i class="fas fa-check-circle"></i> {{ session("status") }}
                    </div>
                @endif
                <p style="font-size:0.85rem;color:#718096;margin:0 0 20px;line-height:1.6;">
                    Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                </p>
                <form method="POST" action="{{ route("password.forgot.send") }}">
                    @csrf
                    <div style="margin-bottom:20px;">
                        <label class="form-label-custom">Correo Electrónico</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:0.82rem;">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" name="email" class="form-control-custom @error("email") is-invalid @enderror"
                                   style="padding-left:32px;" value="{{ old("email") }}"
                                   placeholder="tu@correo.com" autofocus required>
                        </div>
                        @error("email")
                            <div style="color:#e74c3c;font-size:0.78rem;margin-top:4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn-submit-ticket" style="width:100%;justify-content:center;display:flex;align-items:center;gap:8px;font-size:0.9rem;">
                        <i class="fas fa-paper-plane"></i> Enviar enlace de recuperación
                    </button>
                </form>
            </div>
            <div style="border-top:1px solid #f0f2f5;background:#f7f9fc;padding:13px 22px;text-align:center;">
                <a href="{{ route("home") }}" style="font-size:0.82rem;color:#718096;text-decoration:none;">
                    <i class="fas fa-arrow-left me-1"></i> Volver al inicio de sesión
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
