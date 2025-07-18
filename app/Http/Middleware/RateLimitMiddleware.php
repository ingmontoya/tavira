<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$args): Response
    {
        $limitType = $args[0] ?? 'default';
        $key = $this->resolveRequestSignature($request, $limitType);
        
        $limits = $this->getLimits($limitType);
        
        foreach ($limits as $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit['attempts'])) {
                return $this->buildResponse($key, $limit['attempts']);
            }
        }
        
        RateLimiter::hit($key, $limits[0]['decay']);
        
        $response = $next($request);
        
        return $this->addHeaders($response, $key, $limits[0]['attempts']);
    }
    
    /**
     * Resolve the request signature.
     */
    protected function resolveRequestSignature(Request $request, string $limitType): string
    {
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? $request->path();
        
        return match ($limitType) {
            'api' => "api:{$ip}:{$route}",
            'auth' => "auth:{$ip}",
            'upload' => "upload:{$ip}",
            'search' => "search:{$ip}",
            'strict' => "strict:{$ip}:{$route}",
            default => "general:{$ip}:{$route}",
        };
    }
    
    /**
     * Get rate limits for the given type.
     */
    protected function getLimits(string $limitType): array
    {
        return match ($limitType) {
            'api' => [
                ['attempts' => 60, 'decay' => 60], // 60 requests per minute
            ],
            'auth' => [
                ['attempts' => 5, 'decay' => 60], // 5 attempts per minute
            ],
            'upload' => [
                ['attempts' => 10, 'decay' => 60], // 10 uploads per minute
            ],
            'search' => [
                ['attempts' => 30, 'decay' => 60], // 30 searches per minute
            ],
            'strict' => [
                ['attempts' => 10, 'decay' => 60], // 10 requests per minute
            ],
            default => [
                ['attempts' => 100, 'decay' => 60], // 100 requests per minute
            ],
        };
    }
    
    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        return response()->json([
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
        ]);
    }
    
    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(Response $response, string $key, int $maxAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
        ]);
        
        return $response;
    }
}