<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $centralDomains = config('tenancy.central_domains', []);
        $isCentralDomain = in_array($request->getHost(), $centralDomains);

        if ($user->hasVerifiedEmail()) {
            // For central domain users, redirect to subscription plans
            // The redirect.if.subscribed middleware will handle users who already have a subscription
            if ($isCentralDomain) {
                return redirect()->intended(route('subscription.plans', absolute: false).'?verified=1');
            }

            // For tenant users, redirect to dashboard
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            /** @var \Illuminate\Contracts\Auth\MustVerifyEmail $user */
            event(new Verified($user));
        }

        // For central domain users, redirect to subscription plans after verification
        if ($isCentralDomain) {
            return redirect()->intended(route('subscription.plans', absolute: false).'?verified=1');
        }

        // For tenant users, redirect to dashboard
        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
