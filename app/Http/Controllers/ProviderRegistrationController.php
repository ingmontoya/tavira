<?php

namespace App\Http\Controllers;

use App\Models\ProviderRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProviderRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'service_type' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            // Create the provider registration
            $registration = ProviderRegistration::create($validated);

            // Log the registration for admin notification
            Log::info('New provider registration', [
                'id' => $registration->id,
                'company' => $registration->company_name,
                'email' => $registration->email,
            ]);

            // TODO: Send email notification to admin
            // TODO: Send confirmation email to provider

            return redirect()->back()->with('success', '¡Gracias por tu interés! Hemos recibido tu solicitud y nos contactaremos contigo pronto.');
        } catch (\Exception $e) {
            Log::error('Provider registration failed', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.');
        }
    }
}
