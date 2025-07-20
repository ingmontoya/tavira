<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy
        $csp = "default-src 'self'; ".
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net http://localhost:5173; ".
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net; ".
               "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net; ".
               "img-src 'self' data: https:; ".
               "connect-src 'self' http://localhost:8000 http://localhost:5173 ws://localhost:5173; ".
               "media-src 'self'; ".
               "object-src 'none'; ".
               "frame-src 'none'; ".
               "base-uri 'self'; ".
               "form-action 'self'; ".
               "frame-ancestors 'none'; ".
               'upgrade-insecure-requests;';

        // Set security headers
        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
