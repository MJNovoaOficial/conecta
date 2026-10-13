<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Carbon\Carbon;
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

        // Una cuenta desactivada pierde el acceso aunque ya tuviera una sesión
        // abierta en otro navegador antes del cambio administrativo.
        if (!Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('error',
                'Tu cuenta está desactivada. Contacta a un administrador.'
            );
        }

        $timeout = SystemSetting::get('session_timeout_minutes', (int) env('SESSION_TIMEOUT_MINUTES', self::DEFAULT_TIMEOUT));
        if (is_bool($timeout) || filter_var($timeout, FILTER_VALIDATE_INT) === false || $timeout < 1 || $timeout > 1440) {
            $timeout = (int) env('SESSION_TIMEOUT_MINUTES', self::DEFAULT_TIMEOUT);
        }
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity && Carbon::parse($lastActivity)->addMinutes((int) $timeout)->lte(now())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')->with('error',
                'Tu sesión expiró por inactividad. Por favor inicia sesión nuevamente.'
            );
        }

        // Actualizar timestamp de última actividad
        // Las consultas automáticas de la campana no son actividad humana.
        // Se verifica la expiración también en ellas, pero no renuevan la sesión.
        if (!$request->routeIs('notifications.count', 'notifications.recent')) {
            $request->session()->put('last_activity_at', now());
        }

        return $next($request);
    }
}
