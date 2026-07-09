<?php

namespace App\Http\Controllers;

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
        // Rate limiting: máximo 5 intentos por 15 minutos
        $this->rateLimitLogin($request);

        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|max:255',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Log de login exitoso
            Log::info('Usuario autenticado: ' . $request->email, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended('/tickets');
        }

        // Log de intento fallido (sin exponer contraseña)
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
        // Rate limiting: máximo 3 registros por 1 hora desde la misma IP
        $this->rateLimitRegister($request);

        $request->validate([
            'name' => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'email' => 'required|string|email|max:255|unique:usuarios|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'password' => ['required', 'string', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'department_id' => 'required|integer|exists:departamentos,id',
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

    protected function rateLimitLogin(Request $request)
    {
        $throttleKey = 'login:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 15;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\Validator::make([], [])->errors(),
                response()->view('auth.throttle', ['seconds' => $seconds], 429)
            );
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, $decayMinutes * 60);
    }

    protected function rateLimitRegister(Request $request)
    {
        $throttleKey = 'register:' . $request->ip();
        $maxAttempts = 3;
        $decayHours = 1;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\Validator::make([], [])->errors(),
                response()->view('auth.throttle', ['seconds' => $seconds], 429)
            );
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, $decayHours * 3600);
    }
}
