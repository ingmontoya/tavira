<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityPersonnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * API controller for security personnel registration
 * Used by mobile app to register police, security companies, etc.
 */
class SecurityRegistrationController extends Controller
{
    /**
     * Register a new security personnel
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:security_personnel,email',
            'phone' => 'required|string|max:20',
            'organization_type' => 'required|string|in:policia,empresa_seguridad,bomberos,ambulancia',
            'organization_name' => 'nullable|string|max:255',
            'id_number' => 'required|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            'accept_terms' => 'required|accepted',
            'accept_location_tracking' => 'required|accepted',
        ], [
            'email.unique' => 'Este correo ya está registrado',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'accept_terms.accepted' => 'Debes aceptar los términos y condiciones',
            'accept_location_tracking.accepted' => 'Debes autorizar el uso de tu ubicación',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate email verification token
        $verificationToken = Str::random(64);

        // Create security personnel record
        $personnel = SecurityPersonnel::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'organization_type' => $request->organization_type,
            'organization_name' => $request->organization_name,
            'id_number' => $request->id_number,
            'password' => Hash::make($request->password),
            'status' => 'pending_email_verification',
            'accept_terms' => true,
            'accept_location_tracking' => true,
            'email_verification_token' => $verificationToken,
        ]);

        // Send verification email
        $this->sendVerificationEmail($personnel, $verificationToken);

        return response()->json([
            'success' => true,
            'message' => 'Registro exitoso. Revisa tu correo electrónico para verificar tu cuenta.',
            'data' => [
                'id' => $personnel->id,
                'name' => $personnel->name,
                'email' => $personnel->email,
                'status' => $personnel->status,
            ],
        ], 201);
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido',
            ], 422);
        }

        $personnel = SecurityPersonnel::where('email_verification_token', $request->token)->first();

        if (!$personnel) {
            return response()->json([
                'success' => false,
                'message' => 'Token de verificación inválido o expirado',
            ], 404);
        }

        if ($personnel->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Tu correo ya fue verificado. Espera la aprobación del administrador.',
            ]);
        }

        // Verify the email
        $personnel->verifyEmail($request->token);

        return response()->json([
            'success' => true,
            'message' => 'Correo verificado exitosamente. Tu cuenta será revisada por un administrador antes de activarse.',
            'data' => [
                'id' => $personnel->id,
                'name' => $personnel->name,
                'email' => $personnel->email,
                'status' => $personnel->status,
            ],
        ]);
    }

    /**
     * Resend verification email
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Correo electrónico inválido',
            ], 422);
        }

        $personnel = SecurityPersonnel::where('email', $request->email)->first();

        if (!$personnel) {
            // Don't reveal if email exists or not
            return response()->json([
                'success' => true,
                'message' => 'Si el correo está registrado, recibirás un enlace de verificación.',
            ]);
        }

        if ($personnel->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Tu correo ya fue verificado.',
            ]);
        }

        // Generate new token and send email
        $token = $personnel->generateEmailVerificationToken();
        $this->sendVerificationEmail($personnel, $token);

        return response()->json([
            'success' => true,
            'message' => 'Si el correo está registrado, recibirás un enlace de verificación.',
        ]);
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail(SecurityPersonnel $personnel, string $token): void
    {
        $verificationUrl = config('app.url').'/security/verify-email?token='.$token;

        Mail::send('emails.security-verification', [
            'name' => $personnel->name,
            'verificationUrl' => $verificationUrl,
        ], function ($message) use ($personnel) {
            $message->to($personnel->email, $personnel->name)
                ->subject('Verifica tu correo - Tavira Seguridad');
        });
    }
}
