<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsuarioActivoMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Admins y super admins siempre tienen acceso
        if ($user->esAdmin()) {
            return $next($request);
        }

        // Usuario pendiente → redirigir a página de pendiente
        // (excepto si ya está en esa página o está cerrando sesión, para evitar bucles)
        if ($user->estaPendiente() && !$request->routeIs('pendiente', 'pendiente.whatsapp', 'logout')) {
            return redirect()->route('pendiente');
        }

        // Usuario rechazado → cerrar sesión y redirigir
        if ($user->estaRechazado()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('error', 'Tu cuenta ha sido rechazada. Contacta al administrador.');
        }

        return $next($request);
    }
}
