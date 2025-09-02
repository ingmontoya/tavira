<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugEmailVerification
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->hasVerifiedEmail()) {
            Log::info('DebugEmailVerification: User is not verified', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'route' => $request->route()->getName(),
                'intended_url' => $request->fullUrl(),
            ]);
        }

        return $next($request);
    }
}