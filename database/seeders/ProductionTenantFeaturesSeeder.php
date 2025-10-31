<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ProductionTenantFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Configurando features para tenants en producción...');

        // Obtener todos los tenants existentes
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('⚠️  No se encontraron tenants. Ejecute primero el seeder de tenants.');

            return;
        }

        $this->command->info("📋 Encontrados {$tenants->count()} tenants para configurar");

        // Definir plantillas de features
        $featureTemplates = $this->getFeatureTemplates();

        $processedCount = 0;
        $errorCount = 0;

        foreach ($tenants as $tenant) {
            try {
                $this->configureTenantFeatures($tenant, $featureTemplates);
                $processedCount++;
                $this->command->info("✅ Configurado tenant: {$tenant->id}");
            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("❌ Error configurando tenant {$tenant->id}: {$e->getMessage()}");
                Log::error('Error in ProductionTenantFeaturesSeeder', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->command->info('🎉 Configuración completada:');
        $this->command->info("   ✅ Tenants procesados: {$processedCount}");
        if ($errorCount > 0) {
            $this->command->warn("   ❌ Errores: {$errorCount}");
        }

        // Mostrar resumen de features por template
        $this->showFeaturesSummary();
    }

    /**
     * Configure features for a specific tenant
     */
    private function configureTenantFeatures(Tenant $tenant, array $featureTemplates): void
    {
        // Determinar el plan del tenant (puedes personalizar esta lógica)
        $plan = $this->determineTenantPlan($tenant);

        // Obtener features del template
        $template = $featureTemplates[$plan];
        $features = $template['features'];

        $this->command->line("   📦 Aplicando plan '{$template['name']}' a tenant {$tenant->id}");

        // Update marketplace commission based on plan
        $tenant->updateCommissionFromPlan();

        // Configurar cada feature disponible
        $tenant->run(function () use ($tenant, $features) {
            foreach ($this->getAllAvailableFeatures() as $feature) {
                $featureValue = $features[$feature] ?? false;

                // Determinar si el feature está habilitado
                $enabled = $featureValue !== false;

                // Determinar config (si es array, es config; si es boolean true, config es null)
                $config = null;
                if (is_array($featureValue)) {
                    $config = $featureValue;
                }

                TenantFeature::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'feature' => $feature,
                    ],
                    [
                        'enabled' => $enabled,
                        'config' => $config,
                    ]
                );

                if ($enabled) {
                    $configInfo = $config ? ' (con configuración)' : '';
                    $this->command->line("     ✓ {$feature}{$configInfo}");
                }
            }
        });
    }

    /**
     * Determine tenant plan based on tenant data
     */
    private function determineTenantPlan(Tenant $tenant): string
    {
        try {
            // PERSONALIZABLE: Aquí puedes implementar tu lógica para determinar el plan

            // Ejemplo 1: Basado en subscription_plan en los datos del tenant
            if (isset($tenant->data['subscription_plan'])) {
                return match ($tenant->data['subscription_plan']) {
                    'basic' => 'basic',
                    'standard' => 'standard',
                    'premium' => 'premium',
                    default => 'basic'
                };
            }

            // Ejemplo 2: Basado en el campo subscription_plan directo
            if (isset($tenant->subscription_plan)) {
                $planMap = [
                    'basic' => 'basic',
                    'standard' => 'standard',
                    'premium' => 'premium',
                    'pro' => 'premium',
                ];

                return $planMap[$tenant->subscription_plan] ?? 'basic';
            }

            // Ejemplo 3: Basado en patrones en el ID o nombre
            $tenantName = strtolower($tenant->admin_name ?? '');
            $adminEmail = strtolower($tenant->admin_email ?? '');
            $tenantId = strtolower($tenant->id);

            // Buscar indicadores de plan premium
            $premiumKeywords = ['premium', 'pro', 'torres', 'complete'];
            foreach ($premiumKeywords as $keyword) {
                if (str_contains($adminEmail, $keyword) ||
                    str_contains($tenantName, $keyword) ||
                    str_contains($tenantId, $keyword)) {
                    return 'premium';
                }
            }

            // Buscar indicadores de plan estándar
            $standardKeywords = ['standard', 'plus', 'cedros'];
            foreach ($standardKeywords as $keyword) {
                if (str_contains($adminEmail, $keyword) ||
                    str_contains($tenantName, $keyword) ||
                    str_contains($tenantId, $keyword)) {
                    return 'standard';
                }
            }

            // Por defecto: plan básico
            return 'basic';

        } catch (\Exception $e) {
            // En caso de cualquier error, devolver plan básico
            Log::warning("Error determining tenant plan for {$tenant->id}: {$e->getMessage()}");

            return 'basic';
        }
    }

    /**
     * Get feature templates from config
     */
    private function getFeatureTemplates(): array
    {
        return config('feature-plans');
    }

    /**
     * Get all available features (28 total)
     */
    private function getAllAvailableFeatures(): array
    {
        return [
            // ✅ Already implemented (10)
            'correspondence',
            'maintenance_requests',
            'visitor_management',
            'accounting',
            'reservations',
            'announcements',
            'documents',
            'support_tickets',
            'payment_agreements',
            'panic_button',

            // 🔴 To be implemented (18)
            'institutional_email',
            'apartment_management',
            'access_control',
            'analytics_dashboard',
            'internal_messaging',
            'financial_reports',
            'meeting_minutes',
            'system_configuration',
            'basic_administration',
            'voting',
            'audit_logs',
            'push_notifications',
            'resident_management',
            'qr_security_scanner',
            'expense_approvals',
            'advanced_reports',
            'bulk_operations',
            'marketplace_providers', // CRITICAL - Marketplace feature
        ];
    }

    /**
     * Show summary of features configuration
     */
    private function showFeaturesSummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 RESUMEN DE CONFIGURACIÓN:');
        $this->command->info('');

        $templates = $this->getFeatureTemplates();
        foreach ($templates as $key => $template) {
            $this->command->info("🎯 {$template['name']} ({$key}):");
            foreach ($template['features'] as $feature => $value) {
                if ($value !== false) {
                    $configInfo = is_array($value) ? ' (con configuración)' : '';
                    $this->command->info("   ✓ {$feature}{$configInfo}");
                }
            }
            $this->command->info('');
        }

        $this->command->info('💡 PRÓXIMOS PASOS:');
        $this->command->info('   1. Verificar configuración en /tenant-features');
        $this->command->info('   2. Ajustar features individuales si es necesario');
        $this->command->info('   3. Aplicar middleware a las rutas correspondientes');
        $this->command->info('');
    }
}
