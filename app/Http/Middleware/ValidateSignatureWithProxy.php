<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\URL;

/**
 * Custom signature validation middleware that works correctly behind proxies
 *
 * This middleware validates signatures using relative URLs to avoid issues
 * when the application is behind a proxy that might not pass all headers correctly.
 */
class ValidateSignatureWithProxy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Use Laravel's built-in relative signature validation
        // This works correctly behind proxies by using relative URLs
        if (URL::hasValidRelativeSignature($request)) {
            return $next($request);
        }

        // If relative signature fails, try with absolute URL as fallback
        if (URL::hasValidSignature($request)) {
            return $next($request);
        }

        throw new InvalidSignatureException('Invalid signature.');
    }
}
