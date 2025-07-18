<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InputSanitizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->sanitizeInput($request);
        $this->validateInput($request);
        
        return $next($request);
    }
    
    /**
     * Sanitize input data.
     */
    protected function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        
        $sanitized = $this->sanitizeArray($input);
        
        $request->merge($sanitized);
    }
    
    /**
     * Recursively sanitize array data.
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitize string input.
     */
    protected function sanitizeString(string $input): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove dangerous HTML tags but keep basic formatting
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li>');
        
        return $input;
    }
    
    /**
     * Validate input for potential security threats.
     */
    protected function validateInput(Request $request): void
    {
        $input = $request->all();
        
        foreach ($input as $key => $value) {
            if (is_string($value)) {
                $this->checkForSqlInjection($key, $value);
                $this->checkForXss($key, $value);
                $this->checkForPathTraversal($key, $value);
            }
        }
    }
    
    /**
     * Check for SQL injection attempts.
     */
    protected function checkForSqlInjection(string $key, string $value): void
    {
        $sqlPatterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b)/i',
            '/(\bOR\b|\bAND\b)\s*[\'"]\s*[\'"]/i',
            '/;\s*--/i',
            '/\/\*.*?\*\//s',
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                Log::warning('Potential SQL injection attempt detected', [
                    'field' => $key,
                    'value' => $value,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                break;
            }
        }
    }
    
    /**
     * Check for XSS attempts.
     */
    protected function checkForXss(string $key, string $value): void
    {
        $xssPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/expression\s*\(/i',
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                Log::warning('Potential XSS attempt detected', [
                    'field' => $key,
                    'value' => $value,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                break;
            }
        }
    }
    
    /**
     * Check for path traversal attempts.
     */
    protected function checkForPathTraversal(string $key, string $value): void
    {
        $pathPatterns = [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/\/etc\/passwd/',
            '/\/proc\//',
            '/\/var\/log\//',
        ];
        
        foreach ($pathPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                Log::warning('Potential path traversal attempt detected', [
                    'field' => $key,
                    'value' => $value,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
                break;
            }
        }
    }
}