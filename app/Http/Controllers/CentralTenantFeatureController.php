<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CentralTenantFeatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of tenant features
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
        
        // Load features for each tenant directly from their database
        $tenants->getCollection()->transform(function ($tenant) {
            try {
                // Create direct connection to tenant database
                $tenantDbName = 'tenant' . $tenant->id;
                
                // Check if database exists
                $dbExists = DB::select("SELECT datname FROM pg_database WHERE datname = ?", [$tenantDbName]);
                
                if (empty($dbExists)) {
                    $tenant->features = collect([]);
                    return $tenant;
                }
                
                // Configure tenant connection dynamically
                config([
                    "database.connections.{$tenant->id}" => [
                        'driver' => 'pgsql',
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', '5432'),
                        'database' => $tenantDbName,
                        'username' => env('DB_USERNAME', 'forge'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8',
                        'prefix' => '',
                        'prefix_indexes' => true,
                        'schema' => 'public',
                        'sslmode' => 'prefer',
                    ]
                ]);

                // Get features from tenant database
                $features = DB::connection($tenant->id)
                    ->table('tenant_features')
                    ->where('tenant_id', $tenant->id)
                    ->get();
                
                $tenant->features = $features;
                
                // Clean up connection
                DB::purge($tenant->id);
                
            } catch (\Exception $e) {
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

        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return back()->with('error', 'Tenant not found');
            }

            $tenantDbName = 'tenant' . $tenantId;
            
            // Configure tenant connection
            config([
                "database.connections.temp_tenant" => [
                    'driver' => 'pgsql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '5432'),
                    'database' => $tenantDbName,
                    'username' => env('DB_USERNAME', 'forge'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'schema' => 'public',
                    'sslmode' => 'prefer',
                ]
            ]);

            // Update or create the feature
            DB::connection('temp_tenant')
                ->table('tenant_features')
                ->updateOrInsert(
                    ['tenant_id' => $tenantId, 'feature' => $feature],
                    ['enabled' => $request->enabled, 'updated_at' => now()]
                );

            // Clean up connection
            DB::purge('temp_tenant');

            return back()->with('success', 'Feature actualizado correctamente');

        } catch (\Exception $e) {
            \Log::error("Error updating tenant feature: {$e->getMessage()}", [
                'tenant_id' => $tenantId,
                'feature' => $feature
            ]);
            return back()->with('error', 'Error al actualizar feature');
        }
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

        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return back()->with('error', 'Tenant not found');
            }

            $tenantDbName = 'tenant' . $tenantId;
            
            // Configure tenant connection
            config([
                "database.connections.temp_tenant" => [
                    'driver' => 'pgsql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '5432'),
                    'database' => $tenantDbName,
                    'username' => env('DB_USERNAME', 'forge'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'schema' => 'public',
                    'sslmode' => 'prefer',
                ]
            ]);

            // Update features
            foreach ($request->features as $feature => $enabled) {
                DB::connection('temp_tenant')
                    ->table('tenant_features')
                    ->updateOrInsert(
                        ['tenant_id' => $tenantId, 'feature' => $feature],
                        ['enabled' => $enabled, 'updated_at' => now()]
                    );
            }

            // Clean up connection
            DB::purge('temp_tenant');

            return back()->with('success', 'Features actualizados correctamente');

        } catch (\Exception $e) {
            \Log::error("Error bulk updating tenant features: {$e->getMessage()}", [
                'tenant_id' => $tenantId
            ]);
            return back()->with('error', 'Error al actualizar features');
        }
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

        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return back()->with('error', 'Tenant not found');
            }

            $templateFeatures = $templates[$request->template]['features'];
            $tenantDbName = 'tenant' . $tenantId;
            
            // Configure tenant connection
            config([
                "database.connections.temp_tenant" => [
                    'driver' => 'pgsql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '5432'),
                    'database' => $tenantDbName,
                    'username' => env('DB_USERNAME', 'forge'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'schema' => 'public',
                    'sslmode' => 'prefer',
                ]
            ]);
            
            foreach ($this->getAvailableFeatures() as $feature) {
                $enabled = in_array($feature, $templateFeatures);
                DB::connection('temp_tenant')
                    ->table('tenant_features')
                    ->updateOrInsert(
                        ['tenant_id' => $tenantId, 'feature' => $feature],
                        ['enabled' => $enabled, 'updated_at' => now()]
                    );
            }

            // Clean up connection
            DB::purge('temp_tenant');

            return back()->with('success', "Template '{$templates[$request->template]['name']}' aplicado correctamente");

        } catch (\Exception $e) {
            \Log::error("Error applying template: {$e->getMessage()}", [
                'tenant_id' => $tenantId,
                'template' => $request->template
            ]);
            return back()->with('error', 'Error al aplicar template');
        }
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