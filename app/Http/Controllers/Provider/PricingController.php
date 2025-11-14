<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricingController extends Controller
{
    /**
     * Display the pricing page.
     */
    public function index(Request $request)
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            abort(403, 'No tienes un perfil de proveedor asociado.');
        }

        return Inertia::render('Provider/Pricing', [
            'provider' => $provider,
            'mustSelectPlan' => ! $provider->has_seen_pricing,
            'plans' => $this->getPlans(),
            'currentPlan' => $provider->subscription_plan,
        ]);
    }

    /**
     * Mark that provider has viewed the pricing page.
     */
    public function markAsViewed(Request $request)
    {
        $provider = $request->user()->provider;

        if (! $provider) {
            return response()->json(['error' => 'No tienes un perfil de proveedor asociado.'], 403);
        }

        $provider->update([
            'has_seen_pricing' => true,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Subscribe to a plan (for future implementation).
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|in:basico,profesional,premium',
        ]);

        $provider = $request->user()->provider;

        if (! $provider) {
            return back()->with('error', 'No tienes un perfil de proveedor asociado.');
        }

        // For now, just return error since plans are temporarily unavailable
        return back()->with('error', 'Los planes estarán disponibles próximamente.');

        // Future implementation:
        // - Process payment
        // - Update provider subscription_plan
        // - Set commission_rate based on plan
        // - Set leads_remaining based on plan
        // - Set has_b2b2c_access for premium
        // - Set subscription_started_at and subscription_expires_at
    }

    /**
     * Get available subscription plans.
     */
    private function getPlans(): array
    {
        return [
            'basico' => [
                'key' => 'basico',
                'name' => 'BÁSICO',
                'price' => 0,
                'price_display' => 'GRATIS',
                'leads_per_month' => 5,
                'commission_rate' => 11,
                'status' => 'temporarily_unavailable',
                'priority' => 'Baja',
                'support' => 'Email 72h',
                'features' => [
                    '5 leads por mes',
                    '11% de comisión',
                    'Perfil básico',
                    'Soporte por email (72h)',
                    'Prioridad baja en búsquedas',
                ],
            ],
            'profesional' => [
                'key' => 'profesional',
                'name' => 'PROFESIONAL',
                'price' => 89000,
                'price_display' => '$89.000',
                'leads_per_month' => 50,
                'commission_rate' => 8,
                'status' => 'temporarily_unavailable',
                'badge' => 'MÁS POPULAR',
                'priority' => 'Media',
                'support' => 'Email 24h',
                'insignia' => 'Verificado',
                'features' => [
                    '50 leads por mes',
                    '8% de comisión',
                    'Perfil destacado',
                    'Insignia Verificado',
                    'Analytics completo',
                    'Soporte prioritario (24h)',
                    'Prioridad media en búsquedas',
                ],
            ],
            'premium' => [
                'key' => 'premium',
                'name' => 'PREMIUM',
                'price' => 249000,
                'price_display' => '$249.000',
                'leads_per_month' => -1, // Unlimited
                'commission_rate' => 5, // IMPORTANT: 5% not 8%
                'status' => 'temporarily_unavailable',
                'badge' => 'MEJOR VALOR',
                'priority' => 'Alta',
                'support' => 'Teléfono directo',
                'insignia' => 'Premium Oro',
                'exclusive_b2b2c' => true,
                'account_manager' => true,
                'features' => [
                    'Leads ILIMITADOS',
                    '5% de comisión (mejor tarifa)',
                    'Acceso EXCLUSIVO a 300K+ propietarios (B2B2C)',
                    'Account Manager dedicado',
                    'Máxima prioridad en búsquedas',
                    'Insignia Premium Oro',
                    'API y webhooks',
                    'Contratos marco',
                    'Soporte telefónico directo',
                ],
            ],
        ];
    }
}
