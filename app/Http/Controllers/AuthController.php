<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('tickets.index');
        }
        // La página de inicio ya muestra el formulario de login
        return redirect()->route('home');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $request->merge(['email' => strtolower(trim($request->email))]);
        $credentials = [
            ...$request->only('email', 'password'),
            'is_active' => true,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Log de login exitoso (RNF-08)
            LoginAttempt::record($request->email, $request->ip(), $request->userAgent(), true);
            Log::info('Usuario autenticado: ' . $request->email, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended('/tickets');
        }

        // Registro en DB de intento fallido (RNF-08)
        LoginAttempt::record($request->email, $request->ip(), $request->userAgent(), false);
        Log::warning('Intento de login fallido: ' . $request->email, [
            'ip' => $request->ip(),
        ]);

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('auth.register', compact('departments'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email' => 'required|string|email|max:255|unique:usuarios|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'department_id' => 'required|integer|exists:departamentos,id',
        ], [
            'password.required' => 'Ingresa una contraseña.',
            'password.string' => 'La contraseña debe ser un texto válido.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.password.symbols' => 'La contraseña debe incluir al menos un símbolo.',
        ]);

        $user = User::create([
            'name' => trim($request->name),
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'department_id' => $request->department_id,
            'role' => 'user',
            'is_active' => true,
        ]);

        Log::info('Nuevo usuario registrado: ' . $user->email, [
            'ip' => $request->ip(),
        ]);

        Auth::login($user);

        return redirect('/tickets')->with('success', 'Cuenta creada exitosamente');
    }

    public function logout(Request $request)
    {
        Log::info('Usuario desconectado: ' . Auth::user()->email);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Sesión cerrada exitosamente');
    }

}
