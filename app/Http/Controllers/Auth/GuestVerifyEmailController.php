<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Guest email verification controller for mobile app registrations.
 * This allows users to verify their email without being logged in.
 */
class GuestVerifyEmailController extends Controller
{
    /**
     * Verify the user's email address without requiring authentication.
     * This is used for mobile app registrations where users aren't logged in.
     */
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse|Response
    {
        $user = User::find($id);

        if (! $user) {
            return Inertia::render('auth/VerifyEmailResult', [
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ]);
        }

        // Verify the hash matches the user's email
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return Inertia::render('auth/VerifyEmailResult', [
                'success' => false,
                'message' => 'El enlace de verificación no es válido.',
            ]);
        }

        // Check if the signature is valid
        if (! $request->hasValidSignature()) {
            return Inertia::render('auth/VerifyEmailResult', [
                'success' => false,
                'message' => 'El enlace de verificación ha expirado o no es válido.',
            ]);
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return Inertia::render('auth/VerifyEmailResult', [
                'success' => true,
                'message' => 'Tu correo electrónico ya ha sido verificado.',
                'alreadyVerified' => true,
                'requiresApproval' => ! $user->is_active,
            ]);
        }

        // Mark email as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return Inertia::render('auth/VerifyEmailResult', [
            'success' => true,
            'message' => 'Tu correo electrónico ha sido verificado exitosamente.',
            'requiresApproval' => ! $user->is_active,
        ]);
    }
}
