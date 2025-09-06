<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\FeaturesController;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TenantFeatureController extends Controller
{
    protected $featuresController;
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->featuresController = new FeaturesController();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $tenantsQuery = Tenant::query();
        
        if ($request->filled('search')) {
            $tenantsQuery->where(function ($q) use ($request) {
                $q->where('id', 'like', '%'.$request->search.'%')
                  ->orWhere('admin_name', 'like', '%'.$request->search.'%')
                  ->orWhere('admin_email', 'like', '%'.$request->search.'%');
            });
        }

        $tenants = $tenantsQuery->with('features')->paginate(15);

        $availableFeatures = $this->getAvailableFeatures();

        return Inertia::render('CentralDashboard/TenantFeatures/Index', [
            'tenants' => $tenants,
            'availableFeatures' => $availableFeatures,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Update feature status for a tenant
     */
    public function updateFeature(Request $request, string $tenantId, string $feature)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        if ($request->enabled) {
            TenantFeature::enableFeature($tenantId, $feature);
        } else {
            TenantFeature::disableFeature($tenantId, $feature);
        }

        // Invalidate cache for this tenant and feature
        $this->featuresController->invalidateCache($tenantId, $feature);

        return back()->with('success', 'Feature actualizado correctamente');
    }

    /**
     * Bulk update features for a tenant
     */
    public function bulkUpdateFeatures(Request $request, string $tenantId)
    {
        $request->validate([
            'features' => 'required|array',
            'features.*' => 'required|boolean',
        ]);

        foreach ($request->features as $feature => $enabled) {
            if ($enabled) {
                TenantFeature::enableFeature($tenantId, $feature);
            } else {
                TenantFeature::disableFeature($tenantId, $feature);
            }
        }

        // Invalidate cache for this tenant
        $this->featuresController->invalidateCache($tenantId);

        return back()->with('success', 'Features actualizados correctamente');
    }

    /**
     * Apply template features to tenant
     */
    public function applyTemplate(Request $request, string $tenantId)
    {
        $request->validate([
            'template' => 'required|string',
        ]);

        $templates = $this->getFeatureTemplates();
        
        if (!isset($templates[$request->template])) {
            return back()->with('error', 'Template no encontrado');
        }

        $templateFeatures = $templates[$request->template]['features'];
        
        foreach ($this->getAvailableFeatures() as $feature) {
            $enabled = in_array($feature, $templateFeatures);
            if ($enabled) {
                TenantFeature::enableFeature($tenantId, $feature);
            } else {
                TenantFeature::disableFeature($tenantId, $feature);
            }
        }

        // Invalidate cache for this tenant
        $this->featuresController->invalidateCache($tenantId);

        return back()->with('success', "Template '{$templates[$request->template]['name']}' aplicado correctamente");
    }

    protected function getAvailableFeatures(): array
    {
        return [
            'correspondence',
            'maintenance_requests',
            'visitor_management',
            'accounting',
            'reservations',
            'announcements',
            'documents',
            'support_tickets',
            'payment_agreements',
        ];
    }

    protected function getFeatureTemplates(): array
    {
        return [
            'basic' => [
                'name' => 'Plan Básico',
                'features' => ['correspondence', 'announcements', 'support_tickets'],
            ],
            'standard' => [
                'name' => 'Plan Estándar',
                'features' => ['correspondence', 'maintenance_requests', 'announcements', 'support_tickets', 'documents'],
            ],
            'premium' => [
                'name' => 'Plan Premium',
                'features' => ['correspondence', 'maintenance_requests', 'visitor_management', 'accounting', 'reservations', 'announcements', 'documents', 'support_tickets', 'payment_agreements'],
            ],
        ];
    }
}
