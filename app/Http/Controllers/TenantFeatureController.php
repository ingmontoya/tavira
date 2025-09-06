<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\FeaturesController;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $tenants = $tenantsQuery->paginate(15);
        
        // Load features for each tenant from their individual databases
        $tenants->getCollection()->transform(function ($tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    $tenant->features = TenantFeature::where('tenant_id', $tenant->id)->get();
                });
            } catch (\Exception $e) {
                // If tenant database doesn't exist or has issues, provide empty features
                \Log::warning("Error loading tenant features for {$tenant->id}: {$e->getMessage()}");
                $tenant->features = collect([]);
            }
            return $tenant;
        });

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
            // Comunicación y Notificaciones
            'correspondence',
            'announcements',
            'support_tickets',
            'notifications',
            'institutional_email',
            'messaging',
            
            // Administración Básica
            'basic_administration',
            'resident_management',
            'apartment_management',
            
            // Mantenimiento
            'maintenance_requests',
            
            // Gestión de Visitantes y Seguridad
            'visitor_management',
            'security_scanner',
            'access_control',
            
            // Finanzas y Contabilidad
            'accounting',
            'payment_agreements',
            'expense_approvals',
            'financial_reports',
            'provider_management',
            
            // Reservas y Espacios Comunes
            'reservations',
            
            // Documentos y Actas
            'documents',
            'meeting_minutes',
            
            // Asambleas Digitales y Votaciones
            'voting',
            
            // Reportes y Análisis
            'advanced_reports',
            'analytics_dashboard',
            
            // Configuración Avanzada
            'system_settings',
            'audit_logs',
            'bulk_operations',
        ];
    }

    protected function getFeatureTemplates(): array
    {
        return [
            'starter' => [
                'name' => 'Plan Inicial',
                'description' => 'Funcionalidades básicas para conjuntos pequeños',
                'price_monthly' => 89000,
                'max_units' => 50,
                'features' => [
                    'basic_administration',
                    'resident_management', 
                    'apartment_management',
                    'correspondence',
                    'announcements',
                    'support_tickets',
                    'notifications',
                ],
            ],
            'basic' => [
                'name' => 'Plan Básico',
                'description' => 'Gestión integral para conjuntos medianos',
                'price_monthly' => 149000,
                'max_units' => 150,
                'features' => [
                    'basic_administration',
                    'resident_management',
                    'apartment_management',
                    'correspondence',
                    'announcements',
                    'support_tickets',
                    'notifications',
                    'maintenance_requests',
                    'documents',
                    'institutional_email',
                    'messaging',
                ],
            ],
            'standard' => [
                'name' => 'Plan Estándar',
                'description' => 'Control completo con finanzas básicas',
                'price_monthly' => 229000,
                'max_units' => 300,
                'features' => [
                    'basic_administration',
                    'resident_management',
                    'apartment_management',
                    'correspondence',
                    'announcements',
                    'support_tickets',
                    'notifications',
                    'maintenance_requests',
                    'documents',
                    'institutional_email',
                    'messaging',
                    'visitor_management',
                    'accounting',
                    'reservations',
                    'security_scanner',
                ],
            ],
            'professional' => [
                'name' => 'Plan Profesional',
                'description' => 'Gestión avanzada con reportes y análisis',
                'price_monthly' => 349000,
                'max_units' => 500,
                'features' => [
                    'basic_administration',
                    'resident_management',
                    'apartment_management',
                    'correspondence',
                    'announcements',
                    'support_tickets',
                    'notifications',
                    'maintenance_requests',
                    'documents',
                    'meeting_minutes',
                    'institutional_email',
                    'messaging',
                    'visitor_management',
                    'security_scanner',
                    'access_control',
                    'accounting',
                    'payment_agreements',
                    'expense_approvals',
                    'provider_management',
                    'reservations',
                    'advanced_reports',
                    'financial_reports',
                    'voting',
                ],
            ],
            'enterprise' => [
                'name' => 'Plan Empresarial',
                'description' => 'Solución completa para grandes conjuntos',
                'price_monthly' => 549000,
                'max_units' => 1000,
                'features' => [
                    'basic_administration',
                    'resident_management',
                    'apartment_management',
                    'correspondence',
                    'announcements',
                    'support_tickets',
                    'notifications',
                    'maintenance_requests',
                    'documents',
                    'meeting_minutes',
                    'institutional_email',
                    'messaging',
                    'visitor_management',
                    'security_scanner',
                    'access_control',
                    'accounting',
                    'payment_agreements',
                    'expense_approvals',
                    'financial_reports',
                    'provider_management',
                    'reservations',
                    'advanced_reports',
                    'analytics_dashboard',
                    'system_settings',
                    'audit_logs',
                    'bulk_operations',
                    'voting',
                ],
            ],
            'custom' => [
                'name' => 'Plan Personalizado',
                'description' => 'Solución a medida con funcionalidades específicas',
                'price_monthly' => null,
                'max_units' => null,
                'features' => [], // Se configuran manualmente
            ],
        ];
    }
}
