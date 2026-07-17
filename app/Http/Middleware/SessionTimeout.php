<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión si el usuario ha estado inactivo más de SESSION_TIMEOUT_MINUTES.
 * RNF-09: Las sesiones inactivas deberán cerrarse automáticamente.
 */
class SessionTimeout
{
    // Minutos de inactividad antes de cerrar la sesión (configurable en .env como SESSION_TIMEOUT_MINUTES)
    private const DEFAULT_TIMEOUT = 120; // 2 horas

    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplica a usuarios autenticados
        if (!Auth::check()) {
            return $next($request);
        }

        $timeout = (int) env('SESSION_TIMEOUT_MINUTES', self::DEFAULT_TIMEOUT);
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity && (now()->diffInMinutes($lastActivity) >= $timeout)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('error',
                'Tu sesión expiró por inactividad. Por favor inicia sesión nuevamente.'
            );
        }

        // Actualizar timestamp de última actividad
        $request->session()->put('last_activity_at', now());

        return $next($request);
    }
}
