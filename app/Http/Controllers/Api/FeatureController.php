<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConjuntoConfig;
use App\Models\TenantFeature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * Get all available features for the current conjunto/tenant
     */
    public function index(Request $request)
    {
        try {
            $conjuntoConfig = ConjuntoConfig::first();

            if (! $conjuntoConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conjunto configuration not found',
                ], 404);
            }

            // Define available features based on your application modules
            // In a real-world scenario, these might come from a database or configuration
            $features = [
                'voting' => [
                    'enabled' => true, // You can make this configurable per conjunto
                    'metadata' => [
                        'description' => 'Voting and assembly participation',
                        'version' => '1.0.0',
                    ],
                ],
                'maintenance_requests' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Submit and track maintenance requests',
                        'version' => '1.0.0',
                    ],
                ],
                'visitor_management' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Manage visitor access and QR codes',
                        'version' => '1.0.0',
                    ],
                ],
                'financial_reports' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Access financial statements and reports',
                        'version' => '1.0.0',
                    ],
                ],
                'social_hall_reservations' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Reserve common areas and amenities',
                        'version' => '1.0.0',
                    ],
                ],
                'announcements' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'View and confirm announcements',
                        'version' => '1.0.0',
                    ],
                ],
                'correspondence' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Manage postal correspondence',
                        'version' => '1.0.0',
                    ],
                ],
                'notifications' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Push notifications and alerts',
                        'version' => '1.0.0',
                    ],
                ],
                'biometric_auth' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Biometric authentication support',
                        'version' => '1.0.0',
                    ],
                ],
                'dark_mode' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Dark theme support',
                        'version' => '1.0.0',
                    ],
                ],
                'panic_button' => [
                    'enabled' => true,
                    'metadata' => [
                        'description' => 'Emergency panic button for security alerts',
                        'version' => '1.0.0',
                    ],
                ],
            ];

            // Override with actual tenant feature settings from database
            if (function_exists('tenant') && tenant()) {
                $tenantFeatures = TenantFeature::where('tenant_id', tenant('id'))->get();

                foreach ($tenantFeatures as $tenantFeature) {
                    if (isset($features[$tenantFeature->feature])) {
                        $features[$tenantFeature->feature]['enabled'] = $tenantFeature->enabled;
                    }
                }
            }

            // You can add conjunto-specific feature toggles here as fallback
            // For example, if you have a features configuration in conjunto_config
            if (isset($conjuntoConfig->features_config)) {
                $customFeatures = $conjuntoConfig->features_config;
                foreach ($customFeatures as $feature => $config) {
                    if (isset($features[$feature])) {
                        // Only override if not already set by tenant features
                        $tenantFeatureExists = function_exists('tenant') && tenant() &&
                            TenantFeature::where('tenant_id', tenant('id'))
                                ->where('feature', $feature)
                                ->exists();

                        if (! $tenantFeatureExists) {
                            $features[$feature]['enabled'] = $config['enabled'] ?? $features[$feature]['enabled'];
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $features,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving features',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific feature information
     */
    public function show(Request $request, string $feature)
    {
        try {
            $conjuntoConfig = ConjuntoConfig::first();

            if (! $conjuntoConfig) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conjunto configuration not found',
                ], 404);
            }

            // Get all features first
            $allFeatures = $this->getAllFeatures($conjuntoConfig);

            if (! isset($allFeatures[$feature])) {
                return response()->json([
                    'success' => false,
                    'message' => "Feature '{$feature}' not found",
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'feature' => $feature,
                    'enabled' => $allFeatures[$feature]['enabled'],
                    'metadata' => $allFeatures[$feature]['metadata'] ?? [],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving feature',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all features configuration
     */
    private function getAllFeatures(ConjuntoConfig $conjuntoConfig): array
    {
        $features = [
            'voting' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Voting and assembly participation',
                    'version' => '1.0.0',
                ],
            ],
            'maintenance_requests' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Submit and track maintenance requests',
                    'version' => '1.0.0',
                ],
            ],
            'visitor_management' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Manage visitor access and QR codes',
                    'version' => '1.0.0',
                ],
            ],
            'financial_reports' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Access financial statements and reports',
                    'version' => '1.0.0',
                ],
            ],
            'social_hall_reservations' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Reserve common areas and amenities',
                    'version' => '1.0.0',
                ],
            ],
            'announcements' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'View and confirm announcements',
                    'version' => '1.0.0',
                ],
            ],
            'correspondence' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Manage postal correspondence',
                    'version' => '1.0.0',
                ],
            ],
            'notifications' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Push notifications and alerts',
                    'version' => '1.0.0',
                ],
            ],
            'biometric_auth' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Biometric authentication support',
                    'version' => '1.0.0',
                ],
            ],
            'dark_mode' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Dark theme support',
                    'version' => '1.0.0',
                ],
            ],
            'panic_button' => [
                'enabled' => true,
                'metadata' => [
                    'description' => 'Emergency panic button for security alerts',
                    'version' => '1.0.0',
                ],
            ],
        ];

        // Override with actual tenant feature settings from database
        if (function_exists('tenant') && tenant()) {
            $tenantFeatures = TenantFeature::where('tenant_id', tenant('id'))->get();

            foreach ($tenantFeatures as $tenantFeature) {
                if (isset($features[$tenantFeature->feature])) {
                    $features[$tenantFeature->feature]['enabled'] = $tenantFeature->enabled;
                }
            }
        }

        // Apply custom configuration if available (as fallback)
        if (isset($conjuntoConfig->features_config)) {
            $customFeatures = $conjuntoConfig->features_config;
            foreach ($customFeatures as $feature => $config) {
                if (isset($features[$feature])) {
                    // Only override if not already set by tenant features
                    $tenantFeatureExists = function_exists('tenant') && tenant() &&
                        TenantFeature::where('tenant_id', tenant('id'))
                            ->where('feature', $feature)
                            ->exists();

                    if (! $tenantFeatureExists) {
                        $features[$feature]['enabled'] = $config['enabled'] ?? $features[$feature]['enabled'];
                        if (isset($config['metadata'])) {
                            $features[$feature]['metadata'] = array_merge(
                                $features[$feature]['metadata'],
                                $config['metadata']
                            );
                        }
                    }
                }
            }
        }

        return $features;
    }
}
