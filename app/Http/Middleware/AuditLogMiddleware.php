<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $this->logRequest($request, $response, $startTime);

        return $response;
    }

    /**
     * Log the request for audit purposes.
     */
    protected function logRequest(Request $request, Response $response, float $startTime): void
    {
        $duration = microtime(true) - $startTime;

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'status_code' => $response->getStatusCode(),
            'duration' => round($duration * 1000, 2),
            'timestamp' => now()->toISOString(),
        ];

        // Add request data for specific methods
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $logData['request_data'] = $this->sanitizeRequestData($request->all());
        }

        // Log different levels based on status code
        if ($response->getStatusCode() >= 400) {
            Log::warning('HTTP Error Response', $logData);
        } elseif ($this->shouldLogRequest($request)) {
            Log::info('HTTP Request', $logData);
        }

        // Log security-sensitive operations
        if ($this->isSecuritySensitiveOperation($request)) {
            Log::channel('security')->info('Security-sensitive operation', $logData);
        }
    }

    /**
     * Determine if the request should be logged.
     */
    protected function shouldLogRequest(Request $request): bool
    {
        // Don't log asset requests
        if (str_contains($request->path(), 'assets/') ||
            str_contains($request->path(), 'build/') ||
            str_contains($request->path(), 'storage/')) {
            return false;
        }

        // Don't log health checks
        if ($request->path() === 'health') {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a security-sensitive operation.
     */
    protected function isSecuritySensitiveOperation(Request $request): bool
    {
        $securityRoutes = [
            'login',
            'register',
            'password',
            'users',
            'roles',
            'permissions',
        ];

        foreach ($securityRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize request data for logging.
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'remember_token',
            'secret',
            'private_key',
            'credit_card',
            'ssn',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
