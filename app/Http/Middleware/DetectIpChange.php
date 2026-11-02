<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DetectIpChange
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $currentIp = $request->ip();
            $sessionIp = session('user_ip');

            if ($sessionIp && $sessionIp !== $currentIp) {
                Log::warning('Cambio de IP detectado durante sesión', [
                    'user_id' => Auth::id(),
                    'previous_ip' => $sessionIp,
                    'current_ip' => $currentIp,
                ]);

                // Invalidar sesión
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // El inicio contiene el formulario; evitar perder el aviso
                // en la redirección adicional de /login hacia /.
                return redirect()->route('home')->withErrors(['email' => 'Sesión invalidada por cambio de IP. Por favor, inicie sesión nuevamente.']);
            }

            session(['user_ip' => $currentIp]);
        }

        return $next($request);
    }
}
