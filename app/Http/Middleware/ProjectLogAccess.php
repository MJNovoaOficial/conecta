<?php

namespace App\Http\Middleware;

use App\Support\ProjectLogAccess as Access;
use Closure;
use Illuminate\Http\Request;

final class ProjectLogAccess
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Access::staff($request->user()), 403);
        abort_unless(Access::available(), 404);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        return $response;
    }
}
