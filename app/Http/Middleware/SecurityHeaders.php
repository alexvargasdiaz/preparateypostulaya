<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    private const CSP = [
        "default-src 'self'",
        "script-src 'self' 'nonce-{nonce}' https://*.pusher.com http://127.0.0.1:5173",
        "style-src 'self' 'unsafe-inline' http://127.0.0.1:5173",
        "img-src 'self' data: blob: https://www.transparenttextures.com https://images.unsplash.com",
        "font-src 'self' data: http://127.0.0.1:5173",
        "connect-src 'self' https://*.pusher.com wss://*.pusher.com ws://localhost:* http://127.0.0.1:5173 ws://127.0.0.1:5173",
        "frame-src https://www.youtube.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('X-Content-Security-Policy', $this->buildCsp());
        $response->headers->set('Content-Security-Policy', $this->buildCsp());

        return $response;
    }

    private function buildCsp(): string
    {
        $csp = self::CSP;

        // En producción quitamos URLs de Vite dev server (no existen)
        if (!app()->environment('local')) {
            $csp = array_map(fn (string $directive) => trim(str_replace(
                ['http://127.0.0.1:5173', 'ws://127.0.0.1:5173'],
                '',
                $directive
            )), $csp);
        }

        return implode('; ', array_map(
            fn (string $directive) => str_replace('{nonce}', bin2hex(random_bytes(16)), $directive),
            $csp,
        ));
    }
}
