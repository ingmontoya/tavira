<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewProviderRegistration;
use App\Models\Central\ProviderCategory;
use App\Models\ProviderRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * API controller for provider registration
 * Used by mobile app to register service providers
 */
class ProviderRegistrationController extends Controller
{
    /**
     * Get available provider categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = ProviderCategory::active()->ordered()->get([
                'id',
                'name',
                'slug',
                'description',
                'sort_order',
            ]);

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch provider categories', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las categorías',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Store a new provider registration
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'service_type' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:provider_categories,id',
        ], [
            'company_name.required' => 'El nombre de la empresa es requerido',
            'contact_name.required' => 'El nombre de contacto es requerido',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'Por favor ingresa un correo electrónico válido',
            'phone.required' => 'El teléfono es requerido',
            'category_ids.required' => 'Debes seleccionar al menos una categoría de servicio',
            'category_ids.min' => 'Debes seleccionar al menos una categoría de servicio',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create the provider registration
            $registration = ProviderRegistration::create([
                'company_name' => $request->company_name,
                'contact_name' => $request->contact_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'service_type' => $request->service_type,
                'description' => $request->description,
                'status' => 'pending',
            ]);

            // Attach categories
            $registration->categories()->attach($request->category_ids);

            Log::info('New provider registration from mobile', [
                'id' => $registration->id,
                'company' => $registration->company_name,
                'email' => $registration->email,
            ]);

            // Send email notification to all superadmins
            $superadmins = User::role('superadmin')->get();
            foreach ($superadmins as $admin) {
                Mail::to($admin->email)->queue(new NewProviderRegistration($registration));
            }

            Log::info('Admin notifications queued', [
                'registration_id' => $registration->id,
                'admins_notified' => $superadmins->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Gracias por tu interés! Hemos recibido tu solicitud y nos contactaremos contigo pronto.',
                'data' => [
                    'id' => $registration->id,
                    'company_name' => $registration->company_name,
                    'email' => $registration->email,
                    'status' => $registration->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Provider registration failed', [
                'error' => $e->getMessage(),
                'data' => $request->except(['password']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.',
            ], 500);
        }
    }
}
