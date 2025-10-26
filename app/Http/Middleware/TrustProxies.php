<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*'; // Trust all proxies (Kubernetes/nginx)

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Force URL generation to use the correct host and scheme from proxy headers
        // This must happen BEFORE parent::handle() to affect URL generation
        $host = $request->header('X-Forwarded-Host') ?? $request->getHost();
        $scheme = $request->header('X-Forwarded-Proto') ?? $request->getScheme();

        // Only force if we're behind a proxy (not in local development)
        if ($host !== '127.0.0.1' && $host !== 'localhost') {
            app('url')->forceRootUrl($scheme.'://'.$host);
            app('url')->forceScheme($scheme);
        }

        return parent::handle($request, $next);
    }
}
