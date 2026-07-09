@extends('layouts.app')

@section('title', 'Iniciar Sesión - Conecta Mesa de Ayuda')

@section('content')
<div style="min-height: calc(100vh - 52px); display: flex; align-items: center; justify-content: center; padding: 24px;">

    <div style="width: 100%; max-width: 420px;">

        {{-- Logo / Header --}}
        <div style="text-align: center; margin-bottom: 28px;">
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #1a2332; margin: 0 0 4px;">Conecta Soporte</h1>
            <p style="color: #718096; font-size: 0.85rem; margin: 0;">Mesa de Ayuda — Dimak</p>
        </div>

        {{-- Login Card --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.07); overflow: hidden;">

            <div style="background: linear-gradient(90deg, #1a2332, #243447); padding: 14px 22px;">
                <h2 style="color: #fff; font-size: 0.95rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-sign-in-alt me-2" style="color: #3498db;"></i>Iniciar Sesión
                </h2>
            </div>

            <div style="padding: 24px 22px;">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Correo Electrónico</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem;">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email"
                                   name="email"
                                   class="form-control-custom @error('email') is-invalid @enderror"
                                   style="padding-left: 32px;"
                                   value="{{ old('email') }}"
                                   placeholder="tu@correo.com"
                                   autofocus
                                   required>
                        </div>
                        @error('email')
                            <div style="color: #e74c3c; font-size: 0.78rem; margin-top: 4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label class="form-label-custom">Contraseña</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem;">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password"
                                   name="password"
                                   id="passwordField"
                                   class="form-control-custom @error('password') is-invalid @enderror"
                                   style="padding-left: 32px; padding-right: 38px;"
                                   placeholder="••••••••"
                                   required>
                            <button type="button"
                                    onclick="togglePass()"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #a0aec0; cursor: pointer; padding: 0; font-size: 0.82rem;"
                                    id="togglePassBtn">
                                <i class="fas fa-eye" id="passIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div style="color: #e74c3c; font-size: 0.78rem; margin-top: 4px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                        <label style="display: flex; align-items: center; gap: 7px; font-size: 0.82rem; color: #4a5568; cursor: pointer;">
                            <input type="checkbox" name="remember" style="accent-color: #3498db;">
                            Recuérdame
                        </label>
                    </div>

                    <button type="submit" class="btn-submit-ticket" style="width: 100%; justify-content: center; display: flex; align-items: center; gap: 8px; font-size: 0.9rem;">
                        <i class="fas fa-sign-in-alt"></i> Ingresar al Sistema
                    </button>
                </form>
            </div>

            {{-- Footer links --}}
            <div style="border-top: 1px solid #f0f2f5; background: #f7f9fc;">
                <div style="padding: 13px 22px; border-bottom: 1px solid #f0f2f5; text-align: center;">
                    <span style="font-size: 0.82rem; color: #718096;">
                        ¿No tienes cuenta?
                        <a href="{{ route('register') }}" style="color: #3498db; font-weight: 700; text-decoration: none;">
                            Regístrate aquí
                        </a>
                    </span>
                </div>
                <div style="padding: 12px 22px; text-align: center;">
                    <a href="{{ route('tickets.guest.create') }}"
                       style="display: inline-flex; align-items: center; gap: 7px; font-size: 0.8rem; color: #718096; text-decoration: none; padding: 6px 16px; border: 1.5px solid #cbd5e0; border-radius: 6px; transition: all 0.2s; background: #fff;"
                       onmouseenter="this.style.borderColor='#3498db'; this.style.color='#3498db'; this.style.background='#ebf5fb';"
                       onmouseleave="this.style.borderColor='#cbd5e0'; this.style.color='#718096'; this.style.background='#fff';">
                        <i class="fas fa-user-clock"></i>
                        Acceder como invitado sin cuenta
                    </a>
                </div>
            </div>

        </div>

        <p style="text-align: center; color: #a0aec0; font-size: 0.75rem; margin-top: 20px;">
            Conecta © {{ date('Y') }} · Mesa de Ayuda Dimak
        </p>
    </div>
</div>
@endsection

@section('styles')
<style>
.form-label-custom { font-size: 0.82rem; font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
.form-control-custom {
    border: 1.5px solid #e2e8f0;
    border-radius: 7px;
    padding: 9px 12px;
    font-size: 0.875rem;
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
    background: linear-gradient(135deg, #2980b9, #3498db);
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 7px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(41,128,185,0.3);
    width: 100%;
}
.btn-submit-ticket:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(41,128,185,0.4);
}
</style>
@endsection

@section('scripts')
<script>
function togglePass() {
    const field = document.getElementById('passwordField');
    const icon = document.getElementById('passIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
@endsection
