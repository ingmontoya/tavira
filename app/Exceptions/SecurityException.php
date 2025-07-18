<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class SecurityException extends Exception
{
    protected string $securityLevel;
    protected array $context;

    public function __construct(
        string $message = 'Security violation detected',
        int $code = 403,
        string $securityLevel = 'medium',
        array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->securityLevel = $securityLevel;
        $this->context = $context;
        
        $this->logSecurityEvent();
    }

    /**
     * Log the security event.
     */
    protected function logSecurityEvent(): void
    {
        $logData = array_merge([
            'message' => $this->getMessage(),
            'level' => $this->securityLevel,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
        ], $this->context);

        Log::channel('security')->warning('Security Exception', $logData);
    }

    /**
     * Get the security level.
     */
    public function getSecurityLevel(): string
    {
        return $this->securityLevel;
    }

    /**
     * Get the security context.
     */
    public function getContext(): array
    {
        return $this->context;
    }
}