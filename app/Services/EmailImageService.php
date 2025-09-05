<?php

namespace App\Services;

class EmailImageService
{
    /**
     * Get the proper image URL for email templates
     * This ensures Gmail compatibility by using absolute URLs
     */
    public static function getEmailImageUrl(string $imagePath): string
    {
        // Ensure we use the full absolute URL for email clients
        $baseUrl = config('app.url');

        // Remove leading slash if present to avoid double slashes
        $imagePath = ltrim($imagePath, '/');

        // Return the full absolute URL
        return $baseUrl.'/'.$imagePath;
    }

    /**
     * Get image attributes optimized for email clients
     */
    public static function getEmailImageAttributes(
        string $alt = '',
        int $maxHeight = 60,
        string $additionalStyles = ''
    ): array {
        return [
            'alt' => $alt,
            'style' => "max-height: {$maxHeight}px; width: auto; display: block; border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; {$additionalStyles}",
            'border' => '0',
        ];
    }

    /**
     * Check if an image file exists in the public directory
     */
    public static function imageExists(string $imagePath): bool
    {
        $fullPath = public_path($imagePath);

        return file_exists($fullPath);
    }

    /**
     * Get a fallback text if image is not available
     */
    public static function getImageFallback(string $altText = 'Tavira'): string
    {
        return $altText;
    }
}
