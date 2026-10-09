<?php

namespace App\Http\Controllers;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Cache\FileStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        // Usar el correo registrado también agrupa variantes equivalentes
        // para la colación de la base, sin modificar la cuenta existente.
        $accountEmail = User::where('email', $request->email)->value('email') ?? $request->email;
        $throttleKey = 'login:account:' . hash('sha256', $accountEmail);

        try {
            // Serializar solo esta cuenta, nunca toda la IP de una oficina.
            return Cache::lock($throttleKey . ':lock', 120)->block(5,
                fn () => $this->attemptLogin($request, $throttleKey)
            );
        } catch (LockTimeoutException) {
            return $this->loginBusyResponse();
        } catch (\ErrorException $exception) {
            // Windows puede rechazar fopen mientras otro proceso elimina el
            // archivo de un FileLock. No omitir el lock ni autenticar sin él.
            if (! (Cache::getStore() instanceof FileStore)
                || $exception->getSeverity() !== E_WARNING
                || ! str_ends_with(str_replace('\\', '/', $exception->getFile()), '/Illuminate/Filesystem/LockableFile.php')
                || ! str_starts_with($exception->getMessage(), 'fopen(')
                || ! str_contains($exception->getMessage(), 'Permission denied')) {
                throw $exception;
            }
            Log::warning('No se pudo abrir el lock de login por archivos.', ['email' => $accountEmail]);
            return $this->loginBusyResponse();
        }
    }

    protected function loginBusyResponse()
    {
        return response()->view('auth.throttle', [
            'seconds' => 1,
            'message' => 'Se está procesando otro acceso para este correo. Espera unos segundos y vuelve a intentarlo.',
        ], 429)->header('Retry-After', '1');
    }

    protected function attemptLogin(Request $request, string $throttleKey)
    {
        $this->rateLimitLogin($throttleKey);

        $credentials = [
            ...$request->only('email', 'password'),
            'is_active' => true,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            Cache::forget($throttleKey);
            $request->session()->regenerate();

            // Log de login exitoso (RNF-08)
            LoginAttempt::record($request->email, $request->ip(), $request->userAgent(), true);
            Log::info('Usuario autenticado: ' . $request->email, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended('/tickets');
        }

        $this->recordFailedLogin($throttleKey);

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

    protected function rateLimitLogin(string $throttleKey): void
    {
        $state = Cache::get($throttleKey, []);
        $seconds = ($state['blocked_until'] ?? 0) - now()->timestamp;

        if ($seconds > 0) {
            abort(response()->view('auth.throttle', ['seconds' => $seconds], 429)
                ->header('Retry-After', (string) $seconds));
        }
    }

    protected function recordFailedLogin(string $throttleKey): void
    {
        // Se ejecuta bajo el mismo lock que la comprobación y autenticación.
        $now = now()->timestamp;
        $state = Cache::get($throttleKey, []);
        if (($state['window_ends_at'] ?? 0) <= $now) {
            $state = ['failures' => 0, 'window_ends_at' => $now + 15 * 60];
        }

        $state['failures']++;
        if ($state['failures'] >= 5) {
            $state['blocked_until'] = $now + 5 * 60;
            // Al terminar la espera comienza un nuevo contador de fallos.
            Cache::put($throttleKey, $state, 5 * 60);
        } else {
            Cache::put($throttleKey, $state, $state['window_ends_at'] - $now);
        }
    }

    protected function rateLimitRegister(Request $request)
    {
        $throttleKey = 'register:' . $request->ip();
        $maxAttempts = 3;
        $decayHours = 1;

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            abort(response()->view('auth.throttle', ['seconds' => $seconds], 429));
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, $decayHours * 3600);
    }
}
