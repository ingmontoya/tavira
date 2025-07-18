<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadSecurityService
{
    protected array $allowedMimeTypes;
    protected array $disallowedExtensions;
    protected int $maxFileSize;
    
    public function __construct()
    {
        $this->allowedMimeTypes = config('security.uploads.allowed_mime_types', []);
        $this->disallowedExtensions = config('security.uploads.disallowed_extensions', []);
        $this->maxFileSize = config('security.uploads.max_file_size', 10485760);
    }
    
    /**
     * Validate uploaded file security.
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];
        
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . $this->formatBytes($this->maxFileSize);
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            $errors[] = 'File type not allowed: ' . $file->getMimeType();
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->disallowedExtensions)) {
            $errors[] = 'File extension not allowed: ' . $extension;
        }
        
        // Check for double extensions
        if ($this->hasDoubleExtension($file->getClientOriginalName())) {
            $errors[] = 'Files with double extensions are not allowed';
        }
        
        // Check for malicious content
        if ($this->containsMaliciousContent($file)) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        // Check for PHP code in files
        if ($this->containsPhpCode($file)) {
            $errors[] = 'Files containing PHP code are not allowed';
        }
        
        return $errors;
    }
    
    /**
     * Sanitize filename for safe storage.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove or replace dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent hidden files
        if (str_starts_with($filename, '.')) {
            $filename = 'file_' . substr($filename, 1);
        }
        
        // Ensure filename is not too long
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($extension)) . '.' . $extension;
        }
        
        return $filename;
    }
    
    /**
     * Generate secure filename.
     */
    public function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = hash('sha256', $file->getClientOriginalName() . time() . random_bytes(16));
        
        return substr($hash, 0, 32) . '.' . $extension;
    }
    
    /**
     * Store file securely.
     */
    public function storeFile(UploadedFile $file, string $directory = 'uploads'): array
    {
        $validation = $this->validateFile($file);
        
        if (!empty($validation)) {
            return [
                'success' => false,
                'errors' => $validation,
            ];
        }
        
        $filename = $this->generateSecureFilename($file);
        $path = $file->storeAs($directory, $filename, 'public');
        
        // Log file upload
        Log::info('File uploaded successfully', [
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $filename,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
        
        return [
            'success' => true,
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }
    
    /**
     * Check if filename has double extension.
     */
    protected function hasDoubleExtension(string $filename): bool
    {
        $parts = explode('.', $filename);
        
        if (count($parts) < 3) {
            return false;
        }
        
        $dangerousExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp', 'js', 'py', 'pl', 'cgi'];
        
        for ($i = 1; $i < count($parts) - 1; $i++) {
            if (in_array(strtolower($parts[$i]), $dangerousExtensions)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for malicious content in file.
     */
    protected function containsMaliciousContent(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());
        
        if ($content === false) {
            return true;
        }
        
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/base64_decode\s*\(/i',
            '/file_get_contents\s*\(/i',
            '/fopen\s*\(/i',
            '/curl_exec\s*\(/i',
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                Log::warning('Malicious content detected in uploaded file', [
                    'filename' => $file->getClientOriginalName(),
                    'pattern' => $pattern,
                    'user_id' => auth()->id(),
                    'ip' => request()->ip(),
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for PHP code in file.
     */
    protected function containsPhpCode(UploadedFile $file): bool
    {
        $content = file_get_contents($file->getPathname());
        
        if ($content === false) {
            return true;
        }
        
        return preg_match('/<\?(?:php|=)?/i', $content) === 1;
    }
    
    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}