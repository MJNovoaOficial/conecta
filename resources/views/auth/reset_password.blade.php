@extends("layouts.app")
@section("title", "Nueva Contraseña - Conecta")
@section("content")
<div style="min-height:calc(100vh - 52px);display:flex;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:28px;">
            <h1 style="font-size:1.4rem;font-weight:700;color:#1a2332;margin:0 0 4px;">Nueva Contraseña</h1>
            <p style="color:#718096;font-size:0.85rem;margin:0;">Crea una contraseña segura para tu cuenta</p>
        </div>
        <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 20px rgba(0,0,0,0.07);overflow:hidden;">
            <div style="background:linear-gradient(90deg,#1a2332,#243447);padding:14px 22px;">
                <h2 style="color:#fff;font-size:0.95rem;font-weight:600;margin:0;">
                    <i class="fas fa-shield-alt me-2" style="color:#4f8cff;"></i>Restablecer contraseña
                </h2>
            </div>
            <div style="padding:24px 22px;">
                @if($errors->has("email"))
                    <div style="background:#fdecea;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;margin-bottom:18px;color:#b3261e;font-size:0.85rem;">
                        <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first("email") }}
                    </div>
                @endif
                <form method="POST" action="{{ route("password.reset.update") }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">
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
                        <label class="form-label-custom">Confirmar Contraseña</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:0.82rem;"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password_confirmation" id="confPass"
                                   class="form-control-custom" style="padding-left:32px;padding-right:38px;"
                                   placeholder="Repite la contraseña" required minlength="8">
                            <button type="button" onclick="toggleField("confPass","eyeConf")"
                                    style="position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:#5a6a7d;cursor:pointer;padding:6px;font-size:0.95rem;">
                                <i class="fas fa-eye" id="eyeConf"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit-ticket" style="width:100%;justify-content:center;display:flex;align-items:center;gap:8px;font-size:0.9rem;">
                        <i class="fas fa-check"></i> Guardar nueva contraseña
                    </button>
                </form>
            </div>
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
