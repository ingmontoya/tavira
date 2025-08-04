<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Configure trusted proxies
        $this->configureTrustedProxies();
    }

    /**
     * Configure trusted proxies for Cloudflare and Laravel Cloud.
     */
    private function configureTrustedProxies(): void
    {
        // Get trusted proxies from environment or use defaults
        $trustedProxies = env('TRUSTED_PROXIES') ?
            explode(',', env('TRUSTED_PROXIES')) :
            [
                // Cloudflare IPv4 ranges
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '104.16.0.0/13',
                '104.24.0.0/14',
                '108.162.192.0/18',
                '131.0.72.0/22',
                '141.101.64.0/18',
                '162.158.0.0/15',
                '172.64.0.0/13',
                '173.245.48.0/20',
                '188.114.96.0/20',
                '190.93.240.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                // Laravel Cloud internal network
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
            ];

        // Set trusted proxies
        Request::setTrustedProxies(
            $trustedProxies,
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO
        );
    }
}
