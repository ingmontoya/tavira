<?php

namespace App\Console\Commands;

use App\Services\EmailImageService;
use Illuminate\Console\Command;

class TestEmailImages extends Command
{
    protected $signature = 'email:test-images';

    protected $description = 'Test email image URLs and compatibility';

    public function handle()
    {
        $this->info('Testing email image compatibility...');

        $imagePath = 'img/tavira_logo_blanco.svg';

        // Test image existence
        if (EmailImageService::imageExists($imagePath)) {
            $this->info("✓ Image exists: {$imagePath}");
        } else {
            $this->error("✗ Image not found: {$imagePath}");
        }

        // Test URL generation
        $imageUrl = EmailImageService::getEmailImageUrl($imagePath);
        $this->info("Generated URL: {$imageUrl}");

        // Test image attributes
        $attributes = EmailImageService::getEmailImageAttributes('Tavira Logo', 60);
        $this->info('Image attributes:');
        foreach ($attributes as $key => $value) {
            $this->line("  {$key}: {$value}");
        }

        $this->info('✓ Email image testing completed');

        return 0;
    }
}
