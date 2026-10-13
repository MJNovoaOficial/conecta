<?php

namespace App\Http\Middleware;

use App\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySessionSetting
{
    public function handle(Request $request, Closure $next): Response
    {
        $minutes = SystemSetting::get('session_timeout_minutes');
        // Sin parámetro guardado, conservar la configuración actual del servidor.
        // Aplicar antes de StartSession para alinear cookie, driver e inactividad.
        if (!is_bool($minutes) && filter_var($minutes, FILTER_VALIDATE_INT) !== false && $minutes >= 1 && $minutes <= 1440) {
            config(['session.lifetime' => (int) $minutes]);
        }
        return $next($request);
    }
}
