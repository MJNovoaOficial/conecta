@extends('layouts.app')

@section('title', 'Nuevo Usuario — Admin')

@section('styles')
<style>
.form-label-custom { font-size: 0.82rem; font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
.form-control-custom {
    border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 9px 12px;
    font-size: 0.875rem; width: 100%; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s; color: #2d3748; background: #fff;
}
.form-control-custom:focus { border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.12); }
.form-control-custom.is-invalid { border-color: #e74c3c; }
.btn-submit-ticket {
    background: linear-gradient(135deg, #2980b9, #3498db);
    color: #fff; border: none; padding: 10px 20px; border-radius: 7px;
    font-weight: 700; font-size: 0.9rem; cursor: pointer;
    transition: all 0.2s; box-shadow: 0 2px 8px rgba(41,128,185,0.3); width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-ticket:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(41,128,185,0.4); }
.field-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem; pointer-events: none; }
.field-error { color:#e74c3c; font-size:0.78rem; margin-top:4px; }
</style>
@endsection

@section('content')
<div style="min-height: calc(100vh - 52px); display: flex; align-items: center; justify-content: center; padding: 24px;">
    <div style="width: 100%; max-width: 480px;">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 28px;">
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #1a2332; margin: 0 0 4px;">Conecta Soporte</h1>
            <p style="color: #718096; font-size: 0.85rem; margin: 0;">Panel de Administración</p>
        </div>

        {{-- Card --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.07); overflow: hidden;">

            <div style="background: linear-gradient(90deg, #1a2332, #243447); padding: 14px 22px;">
                <h2 style="color: #fff; font-size: 0.95rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-user-plus me-2" style="color: #3498db;"></i>Nuevo Usuario
                </h2>
            </div>

            <div style="padding: 24px 22px;">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    {{-- Nombre --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Nombre Completo</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-user"></i></span>
                            <input type="text" name="name"
                                   class="form-control-custom @error('name') is-invalid @enderror"
                                   style="padding-left: 32px;"
                                   value="{{ old('name') }}"
                                   placeholder="Nombre completo del usuario"
                                   autofocus required>
                        </div>
                        @error('name')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Email --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Correo Electrónico</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email"
                                   class="form-control-custom @error('email') is-invalid @enderror"
                                   style="padding-left: 32px;"
                                   value="{{ old('email') }}"
                                   placeholder="correo@empresa.com" required>
                        </div>
                        @error('email')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Departamento --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Departamento</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-building"></i></span>
                            <select name="department_id"
                                    class="form-control-custom @error('department_id') is-invalid @enderror"
                                    style="padding-left: 32px;" required>
                                <option value="">Selecciona un departamento...</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('department_id')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Rol --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Rol</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-shield-alt"></i></span>
                            <select name="role"
                                    class="form-control-custom @error('role') is-invalid @enderror"
                                    style="padding-left: 32px;" required>
                                <option value="user"    {{ old('role') === 'user'    ? 'selected' : '' }}>Usuario</option>
                                <option value="support" {{ old('role') === 'support' ? 'selected' : '' }}>Soporte</option>
                                <option value="admin"   {{ old('role') === 'admin'   ? 'selected' : '' }}>Administrador</option>
                            </select>
                        </div>
                        @error('role')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Contraseña --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Contraseña</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="passField"
                                   class="form-control-custom @error('password') is-invalid @enderror"
                                   style="padding-left: 32px; padding-right: 38px;"
                                   placeholder="Mínimo 8 caracteres" required>
                            <button type="button" onclick="togglePass()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#a0aec0;cursor:pointer;padding:0;font-size:0.82rem;">
                                <i class="fas fa-eye" id="passIcon"></i>
                            </button>
                        </div>
                        @error('password')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Confirmar contraseña --}}
                    <div style="margin-bottom: 20px;">
                        <label class="form-label-custom">Confirmar Contraseña</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password_confirmation"
                                   class="form-control-custom"
                                   style="padding-left: 32px;"
                                   placeholder="Repite la contraseña" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-ticket">
                        <i class="fas fa-user-plus"></i> Crear Usuario
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <div style="border-top: 1px solid #f0f2f5; background: #f7f9fc; padding: 13px 22px; text-align: center;">
                <a href="{{ route('admin.users') }}" style="font-size: 0.82rem; color: #718096; text-decoration: none;">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Usuarios
                </a>
            </div>
        </div>

        <p style="text-align: center; color: #a0aec0; font-size: 0.75rem; margin-top: 20px;">
            Conecta © {{ date('Y') }} · Mesa de Ayuda
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
function togglePass() {
    const f = document.getElementById('passField');
    const i = document.getElementById('passIcon');
    if (f.type === 'password') { f.type = 'text'; i.classList.replace('fa-eye','fa-eye-slash'); }
    else { f.type = 'password'; i.classList.replace('fa-eye-slash','fa-eye'); }
}
</script>
@endsection
