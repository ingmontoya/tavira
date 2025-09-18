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
            // If user is verified, redirect to intended URL or dashboard
            // Using intended() will use the URL they were trying to access before verification
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // If not verified, show the verification notice page
        return Inertia::render('auth/VerifyEmail', ['status' => $request->session()->get('status')]);
    }
}
