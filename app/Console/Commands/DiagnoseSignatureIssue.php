<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseSignatureIssue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:signature {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose signature validation issues with email verification URLs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Signature Validation Diagnostics ===');
        $this->newLine();

        // 1. Check APP_KEY
        $this->info('1. APP_KEY Configuration:');
        $appKey = config('app.key');
        $this->line('   Configured: '.(! empty($appKey) ? '✓ Yes' : '✗ No'));
        $this->line('   Starts with base64: '.(str_starts_with($appKey, 'base64:') ? '✓ Yes' : '✗ No'));
        $this->line('   Length: '.strlen($appKey).' chars');
        $this->newLine();

        // 2. Check URL configuration
        $this->info('2. URL Configuration:');
        $this->line('   APP_URL: '.config('app.url'));
        $this->line('   Current URL: '.url('/'));
        $this->line('   Environment: '.config('app.env'));
        $this->line('   Force HTTPS: '.(config('app.env') === 'production' ? '✓ Yes' : '✗ No'));
        $this->newLine();

        // 3. Check proxy configuration
        $this->info('3. Proxy Configuration:');
        $this->line('   Trusted Proxies: *');
        $this->line('   Middleware: TrustProxies');
        $this->newLine();

        // 4. Generate a test verification URL
        $this->info('4. Test Verification URL Generation:');
        $testUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 1, 'hash' => sha1('test@example.com')]
        );
        $this->line('   Generated URL:');
        $this->line('   '.$testUrl);
        $this->newLine();

        // 5. Parse the URL and show components
        $parsedUrl = parse_url($testUrl);
        $this->info('5. URL Components:');
        $this->line('   Scheme: '.($parsedUrl['scheme'] ?? 'N/A'));
        $this->line('   Host: '.($parsedUrl['host'] ?? 'N/A'));
        $this->line('   Port: '.($parsedUrl['port'] ?? 'default'));
        $this->line('   Path: '.($parsedUrl['path'] ?? 'N/A'));

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            $this->line('   Query params:');
            foreach ($queryParams as $key => $value) {
                if ($key === 'signature') {
                    $this->line('     - '.$key.': '.substr($value, 0, 20).'...');
                } else {
                    $this->line('     - '.$key.': '.$value);
                }
            }
        }
        $this->newLine();

        // 6. If a URL was provided, validate it
        if ($url = $this->argument('url')) {
            $this->info('6. Validating Provided URL:');
            $this->line('   URL: '.$url);

            // Create a fake request with the URL
            $request = \Illuminate\Http\Request::create($url);

            // Try relative signature validation
            $relativeValid = \Illuminate\Support\Facades\URL::hasValidRelativeSignature($request);
            $this->line('   Relative Signature Valid: '.($relativeValid ? '✓ Yes' : '✗ No'));

            // Try absolute signature validation
            $absoluteValid = \Illuminate\Support\Facades\URL::hasValidSignature($request);
            $this->line('   Absolute Signature Valid: '.($absoluteValid ? '✓ Yes' : '✗ No'));

            $this->newLine();
        }

        $this->info('=== Diagnostics Complete ===');
        $this->newLine();

        $this->comment('Common issues:');
        $this->line('  • URL expired: Check the expires parameter');
        $this->line('  • Domain mismatch: Ensure the URL domain matches your APP_URL');
        $this->line('  • APP_KEY changed: If APP_KEY changed, old URLs are invalid');
        $this->line('  • Proxy headers: Check X-Forwarded-* headers are being sent correctly');

        return 0;
    }
}
