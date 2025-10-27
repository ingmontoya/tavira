<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Show the email verification prompt page.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            $centralDomains = config('tenancy.central_domains', []);
            $isCentralDomain = in_array($request->getHost(), $centralDomains);

            // For central domain users, redirect to subscription plans
            // The redirect.if.subscribed middleware will handle users who already have a subscription
            if ($isCentralDomain) {
                return redirect()->intended(route('subscription.plans', absolute: false));
            }

            // For tenant users, redirect to dashboard
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // If not verified, show the verification notice page
        return Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
