<?php

namespace App\Http\Controllers;

use App\Models\SecurityPersonnel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for security personnel email verification
 * Handles GET requests from verification email links
 */
class SecurityEmailVerificationController extends Controller
{
    /**
     * Verify security personnel email with token
     */
    public function verify(Request $request): Response
    {
        $token = $request->query('token');

        if (!$token || strlen($token) !== 64) {
            return Inertia::render('auth/SecurityVerifyEmailResult', [
                'success' => false,
                'message' => 'El enlace de verificacion no es valido.',
            ]);
        }

        $personnel = SecurityPersonnel::where('email_verification_token', $token)->first();

        if (!$personnel) {
            return Inertia::render('auth/SecurityVerifyEmailResult', [
                'success' => false,
                'message' => 'El enlace de verificacion ha expirado o no es valido.',
            ]);
        }

        // Check if already verified
        if ($personnel->hasVerifiedEmail()) {
            return Inertia::render('auth/SecurityVerifyEmailResult', [
                'success' => true,
                'message' => 'Tu correo electronico ya ha sido verificado.',
                'alreadyVerified' => true,
                'status' => $personnel->status,
            ]);
        }

        // Verify the email
        $personnel->verifyEmail($token);

        return Inertia::render('auth/SecurityVerifyEmailResult', [
            'success' => true,
            'message' => 'Tu correo electronico ha sido verificado exitosamente.',
            'status' => 'pending_admin_approval',
        ]);
    }
}
