<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        try {
            $canRegisterAdmin = ! User::role(['superadmin', 'admin_conjunto'])->exists();
        } catch (\Exception $e) {
            // If roles don't exist yet (e.g., in a new tenant), allow admin registration
            $canRegisterAdmin = true;
        }

        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'canRegisterAdmin' => $canRegisterAdmin,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $request->authenticate();

        // If this is an API request, return JSON response with token
        if ($request->expectsJson() || $request->is('api/*')) {
            $user = Auth::user();
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user->load('resident.apartment'),
                'message' => 'Login successful',
            ]);
        }

        // For web requests, regenerate session and redirect
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        // If this is an API request, revoke token and return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            $user = Auth::user();

            // Revoke current token
            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

            return response()->json([
                'message' => 'Logout successful',
            ]);
        }

        // For web requests, use session-based logout
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
