<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * API controller for resident registration from mobile app
 * This allows residents to self-register by selecting their apartment
 */
class ResidentRegistrationController extends Controller
{
    /**
     * Handle resident registration from mobile app
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'tower' => 'required|string|max:50',
            'apartment' => 'required|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            'accept_terms' => 'required|accepted',
        ], [
            'name.required' => 'El nombre es requerido',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico no es válido',
            'email.unique' => 'Este correo electrónico ya está registrado',
            'tower.required' => 'La torre es requerida',
            'apartment.required' => 'El apartamento es requerido',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'accept_terms.required' => 'Debes aceptar los términos y condiciones',
            'accept_terms.accepted' => 'Debes aceptar los términos y condiciones',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the apartment by tower and number
        $apartment = Apartment::where('tower', $request->tower)
            ->where('number', $request->apartment)
            ->first();

        if (! $apartment) {
            return response()->json([
                'message' => 'No se encontró el apartamento especificado',
                'errors' => ['apartment' => ['El apartamento no existe en esta torre']],
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => strtolower(trim($request->email)),
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Assign resident role
            $user->assignRole('residente');

            // Split the full name into first and last names
            $nameParts = explode(' ', $request->name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Check if resident already exists with this email
            $existingResident = Resident::where('email', strtolower(trim($request->email)))->first();

            if (! $existingResident) {
                // Create a resident record
                Resident::create([
                    'apartment_id' => $apartment->id,
                    'user_id' => $user->id,
                    'document_type' => 'CC',
                    'document_number' => 'PENDIENTE_'.$user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => strtolower(trim($request->email)),
                    'phone' => $request->phone,
                    'resident_type' => 'Tenant', // Default to tenant, admin can change later
                    'start_date' => now()->toDateString(),
                ]);
            } else {
                // Update existing resident to link with user
                $existingResident->update([
                    'user_id' => $user->id,
                    'apartment_id' => $apartment->id,
                ]);
            }

            DB::commit();

            // Fire registered event (for email verification, etc.)
            event(new Registered($user));

            // Generate API token
            $token = $user->createToken('mobile-app')->plainTextToken;

            // Load user with relationships
            $user->load(['resident.apartment.apartmentType', 'roles']);

            $userData = $user->toArray();
            $userData['role'] = $user->roles->first()?->name ?? 'residente';

            return response()->json([
                'message' => 'Registro exitoso',
                'token' => $token,
                'user' => $userData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear la cuenta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
