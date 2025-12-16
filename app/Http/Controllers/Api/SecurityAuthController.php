<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityPersonnel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * API controller for security personnel authentication
 * Handles login/logout for police, security companies, etc.
 */
class SecurityAuthController extends Controller
{
    /**
     * Login security personnel
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es requerido',
            'password.required' => 'La contraseña es requerida',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $personnel = SecurityPersonnel::where('email', $request->email)->first();

        if (!$personnel || !Hash::check($request->password, $personnel->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        // Check account status
        if ($personnel->isPendingEmailVerification()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión.',
                'status' => 'pending_email_verification',
            ], 403);
        }

        if ($personnel->isPendingAdminApproval()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta está pendiente de aprobación por un administrador.',
                'status' => 'pending_admin_approval',
            ], 403);
        }

        if ($personnel->isRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu solicitud de registro fue rechazada.',
                'status' => 'rejected',
                'reason' => $personnel->rejection_reason,
            ], 403);
        }

        if ($personnel->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta ha sido suspendida.',
                'status' => 'suspended',
                'reason' => $personnel->rejection_reason,
            ], 403);
        }

        if (!$personnel->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta no está activa.',
                'status' => $personnel->status,
            ], 403);
        }

        // Generate token for API authentication
        $token = Str::random(60);

        // For now, store the token in the session or return it directly
        // In a real implementation, you would use Sanctum or Passport
        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'user' => [
                'id' => $personnel->id,
                'name' => $personnel->name,
                'email' => $personnel->email,
                'phone' => $personnel->phone,
                'organization_type' => $personnel->organization_type,
                'organization_name' => $personnel->organization_name,
                'role' => 'security_' . $personnel->organization_type,
            ],
        ]);
    }

    /**
     * Get current authenticated security personnel
     */
    public function me(Request $request): JsonResponse
    {
        // This would require proper token validation with Sanctum/Passport
        // For now, return a placeholder response
        return response()->json([
            'success' => false,
            'message' => 'Authentication required',
        ], 401);
    }

    /**
     * Logout security personnel
     */
    public function logout(Request $request): JsonResponse
    {
        // This would invalidate the token
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Check account status (for pre-login check)
     */
    public function checkStatus(Request $request): JsonResponse
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
            // Don't reveal if email exists
            return response()->json([
                'success' => true,
                'exists' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'exists' => true,
            'status' => $personnel->status,
            'can_login' => $personnel->canLogin(),
        ]);
    }
}
