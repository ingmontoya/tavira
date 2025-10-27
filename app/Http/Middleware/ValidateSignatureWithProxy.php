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
        // Check if the signature is valid using relative URL
        if ($this->hasValidRelativeSignature($request)) {
            return $next($request);
        }

        // If relative signature fails, try with absolute URL as fallback
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        throw new InvalidSignatureException('Invalid signature.');
    }

    /**
     * Determine if the given request has a valid signature using relative URL.
     */
    protected function hasValidRelativeSignature(Request $request): bool
    {
        // Get the signature from the request
        $signature = $request->query('signature', '');

        if (empty($signature)) {
            return false;
        }

        // Build the URL without the signature parameter
        $url = $request->url();
        $queryString = $this->buildQueryString($request->except('signature'));

        if ($queryString) {
            $url .= '?' . $queryString;
        }

        // Get and decode APP_KEY (Laravel stores it as base64:xxx)
        $key = $this->getAppKey();

        // Generate expected signature for this URL
        $expectedSignature = hash_hmac('sha256', $url, $key);

        // Compare signatures
        return hash_equals($expectedSignature, (string) $signature);
    }

    /**
     * Get the application key, decoding it if needed.
     */
    protected function getAppKey(): string
    {
        $key = config('app.key');

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
    }

    /**
     * Build query string from parameters.
     */
    protected function buildQueryString(array $parameters): string
    {
        return http_build_query($parameters);
    }
}
