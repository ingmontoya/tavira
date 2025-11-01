<?php

namespace App\Http\Controllers;

use App\Services\WompiPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubscriptionPaymentController extends Controller
{
    protected $wompiService;

    public function __construct(WompiPaymentService $wompiService)
    {
        $this->wompiService = $wompiService;
    }

    /**
     * Show subscription plans page
     */
    public function index()
    {
        // Get plans from config to match feature-flags system
        $planConfigs = config('feature-plans');

        $plans = [
            [
                'id' => 'basic',
                'name' => 'Plan Básico',
                'price' => $planConfigs['basic']['price'],
                'billing' => 'mensual',
                'max_units' => $planConfigs['basic']['max_units'],
                'features' => [
                    'Hasta ' . $planConfigs['basic']['max_units'] . ' apartamentos',
                    'Correspondencia',
                    'Anuncios',
                    'Tickets de soporte (PQRS)',
                    'Gestión básica de apartamentos y residentes',
                    'Dashboard analítico básico',
                    'Control de acceso básico',
                    'Notificaciones push',
                    'Gestión de documentos (1GB)',
                    'Marketplace de proveedores (3 cotizaciones/mes)',
                ],
                'popular' => false,
            ],
            [
                'id' => 'standard',
                'name' => 'Plan Estándar',
                'price' => $planConfigs['standard']['price'],
                'billing' => 'mensual',
                'max_units' => $planConfigs['standard']['max_units'],
                'features' => [
                    'Hasta ' . $planConfigs['standard']['max_units'] . ' apartamentos',
                    'Todo lo del plan Básico',
                    'Solicitudes de mantenimiento (50/mes)',
                    'Correo institucional',
                    'Mensajería interna',
                    'Reportes financieros básicos',
                    'Aprobación de gastos (flujo simple)',
                    'Logs de auditoría (30 días)',
                    'Gestión de documentos (3GB)',
                    'Marketplace de proveedores (10 cotizaciones/mes)',
                ],
                'popular' => true,
            ],
            [
                'id' => 'premium',
                'name' => 'Plan Premium',
                'price' => $planConfigs['premium']['price'],
                'billing' => 'mensual',
                'max_units' => $planConfigs['premium']['max_units'],
                'features' => [
                    'Hasta ' . $planConfigs['premium']['max_units'] . ' apartamentos',
                    'Todo lo del plan Estándar',
                    'Gestión de visitantes',
                    'Contabilidad completa',
                    'Reservas de espacios comunes',
                    'Acuerdos de pago (hasta 6 cuotas)',
                    'Actas de reuniones',
                    'Votaciones (2 al año)',
                    'Botón de pánico básico',
                    'Escáner QR de seguridad',
                    'Reportes avanzados',
                    'Operaciones masivas',
                    'Gestión de documentos (10GB)',
                    'Marketplace profesional (cotizaciones ilimitadas)',
                    'Licitaciones digitales',
                    'Contratos digitales',
                ],
                'popular' => false,
            ],
            [
                'id' => 'enterprise',
                'name' => 'Plan Enterprise',
                'price' => $planConfigs['enterprise']['price'],
                'billing' => 'mensual',
                'max_units' => 'ilimitados',
                'features' => [
                    'Apartamentos ilimitados',
                    'Todas las funcionalidades sin restricciones',
                    'Dashboard BI empresarial',
                    'Mantenimiento predictivo',
                    'Multi-propiedad',
                    'Acceso API',
                    'Subdominio personalizado',
                    'Branding personalizado',
                    'Soporte dedicado 24/7 con SLA',
                    'Account manager exclusivo',
                    'Logs de auditoría (365 días)',
                    'Gestión de documentos (50GB)',
                    'Marketplace VIP',
                    'Subastas inversas',
                    'Acuerdos marco',
                    'Negociación por volumen',
                    'Dashboard ejecutivo',
                ],
                'popular' => false,
            ],
        ];

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
            'csrf_token' => csrf_token(),
        ]);
    }

    /**
     * Create payment link for subscription
     */
    public function createPaymentLink(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|in:basic,standard,premium,enterprise',
            'tenant_id' => 'nullable|exists:tenants,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:20',
            'conjunto_name' => 'required|string|max:255',
            'conjunto_address' => 'nullable|string|max:500',
            'billing_type' => 'required|in:mensual,anual',
        ]);

        try {
            // Get plan prices from config
            $planConfigs = config('feature-plans');

            // Define plan prices (annual = monthly * 10, with 2 months discount)
            $planPrices = [
                'basic' => [
                    'mensual' => $planConfigs['basic']['price'],
                    'anual' => $planConfigs['basic']['price'] * 10,
                ],
                'standard' => [
                    'mensual' => $planConfigs['standard']['price'],
                    'anual' => $planConfigs['standard']['price'] * 10,
                ],
                'premium' => [
                    'mensual' => $planConfigs['premium']['price'],
                    'anual' => $planConfigs['premium']['price'] * 10,
                ],
                'enterprise' => [
                    'mensual' => $planConfigs['enterprise']['price'],
                    'anual' => $planConfigs['enterprise']['price'] * 10,
                ],
            ];

            $planNames = [
                'basic' => 'Plan Básico',
                'standard' => 'Plan Estándar',
                'premium' => 'Plan Premium',
                'enterprise' => 'Plan Enterprise',
            ];

            $planId = $request->plan_id;
            $billingType = $request->billing_type;
            $amount = $planPrices[$planId][$billingType];
            $planName = ($billingType === 'anual' ? 'ANUAL_' : '').strtoupper($planId);

            $subscriptionData = [
                'tenant_id' => $request->tenant_id,
                'user_id' => auth()->id(), // Associate with current user
                'plan_name' => $planName,
                'amount' => $amount,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'conjunto_name' => $request->conjunto_name,
                'conjunto_address' => $request->conjunto_address,
                'billing_type' => $billingType,
                'city' => $request->city ?? 'Bogotá',
                'region' => $request->region ?? 'Bogotá D.C.',
            ];

            $options = [
                'redirect_url' => route('subscription.success'),
                'expiration_hours' => 72,
                'city' => $request->city ?? 'Bogotá',
                'region' => $request->region ?? 'Bogotá D.C.',
            ];

            $result = $this->wompiService->createSubscriptionPaymentLink($subscriptionData, $options);

            if ($result['success']) {
                // Store payment link info for tracking
                $paymentData = [
                    'payment_link_id' => $result['payment_link_id'],
                    'reference' => $result['reference'],
                    'plan_name' => $planName,
                    'amount' => $amount,
                    'customer_data' => $subscriptionData,
                    'created_at' => now(),
                ];

                // Store in session or database for tracking
                session(['pending_subscription_payment' => $paymentData]);

                return response()->json([
                    'success' => true,
                    'payment_link' => $result['payment_link'],
                    'reference' => $result['reference'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Error creating payment link',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error creating subscription payment link', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // In production, provide more specific error information for debugging
            $errorMessage = config('app.debug') ? $e->getMessage() : 'Error interno del servidor';

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_details' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Handle successful payment redirect
     */
    public function paymentSuccess(Request $request)
    {
        $reference = $request->get('reference');
        $transactionId = $request->get('id');

        if ($reference && $transactionId) {
            // Get transaction details from Wompi
            $transactionResult = $this->wompiService->getTransactionById($transactionId);

            if ($transactionResult['success'] && $transactionResult['transaction']) {
                $transaction = $transactionResult['transaction'];

                if ($transaction['status'] === 'APPROVED') {
                    Log::info('Processing successful payment in success page', [
                        'transaction_id' => $transaction['id'],
                        'reference' => $transaction['reference'],
                        'status' => $transaction['status'],
                    ]);

                    // Process the successful payment
                    $paymentResult = $this->wompiService->processSuccessfulSubscriptionPayment($transaction);

                    Log::info('Payment processing result', [
                        'success' => $paymentResult['success'],
                        'has_subscription' => isset($paymentResult['subscription']),
                        'subscription_id' => $paymentResult['subscription']->id ?? null,
                    ]);

                    if ($paymentResult['success']) {
                        // Payment was processed successfully, redirect to dashboard
                        Log::info('Payment successful, redirecting to dashboard', [
                            'subscription_id' => $paymentResult['subscription']->id ?? null,
                            'user_id' => auth()->id(),
                        ]);

                        return redirect()->route('dashboard')
                            ->with('success', 'Pago procesado exitosamente. ¡Bienvenido a Tavira!')
                            ->with('subscription', $paymentResult['subscription'])
                            ->with('new_subscription', true);
                    } else {
                        Log::error('Payment processing failed', [
                            'error' => $paymentResult['error'] ?? 'Unknown error',
                            'transaction_id' => $transaction['id'],
                        ]);
                    }
                }
            }
        }

        // If we get here, either no reference/id was provided or transaction wasn't approved
        // Check if user now has an active subscription (might have been processed via webhook)
        $user = auth()->user();
        if ($user) {
            $activeSubscription = \App\Models\TenantSubscription::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($q) {
                        $q->whereNull('tenant_id')->whereNull('user_id'); // New signups
                    });
            })
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($activeSubscription) {
                Log::info('User has active subscription after payment, redirecting to dashboard', [
                    'user_id' => $user->id,
                    'subscription_id' => $activeSubscription->id,
                ]);

                return redirect()->route('dashboard')
                    ->with('success', '¡Pago procesado exitosamente! Bienvenido a Tavira.')
                    ->with('new_subscription', true);
            }
        }

        // Fallback: show success page with verification message
        return Inertia::render('Subscription/Success', [
            'transaction' => null,
            'message' => 'Verificando estado del pago...',
            'reference' => $request->get('reference'),
            'transaction_id' => $request->get('id'),
        ]);
    }

    /**
     * Handle payment failure redirect
     */
    public function paymentFailure(Request $request)
    {
        $reference = $request->get('reference');
        $transactionId = $request->get('id');

        Log::warning('Subscription payment failed', [
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'request' => $request->all(),
        ]);

        return Inertia::render('Subscription/Failure', [
            'reference' => $reference,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Webhook endpoint for Wompi payment notifications
     */
    public function webhook(Request $request)
    {
        Log::info('Wompi webhook received', $request->all());

        try {
            // Verify webhook signature
            if (! $this->wompiService->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature from Wompi');

                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = $request->input('event');
            $data = $request->input('data');

            if ($event === 'transaction.updated' && isset($data['transaction'])) {
                $transaction = $data['transaction'];

                Log::info('Webhook transaction received', [
                    'transaction_id' => $transaction['id'],
                    'reference' => $transaction['reference'],
                    'status' => $transaction['status'],
                    'payment_link_id' => $transaction['payment_link_id'] ?? null,
                    'redirect_url' => $transaction['redirect_url'] ?? null,
                ]);

                // Check if this is a subscription payment by:
                // 1. Reference starts with SUB- (our custom reference)
                // 2. OR redirect_url contains subscription (payment link based)
                $isSubscriptionPayment = str_starts_with($transaction['reference'], 'SUB-') ||
                                        str_contains($transaction['redirect_url'] ?? '', 'subscription');

                if ($isSubscriptionPayment) {
                    if ($transaction['status'] === 'APPROVED') {
                        Log::info('Processing subscription payment via webhook', [
                            'transaction_id' => $transaction['id'],
                            'reference' => $transaction['reference'],
                        ]);

                        $result = $this->wompiService->processSuccessfulSubscriptionPayment($transaction);

                        if ($result['success']) {
                            Log::info('Subscription payment processed via webhook', [
                                'reference' => $transaction['reference'],
                                'transaction_id' => $transaction['id'],
                                'subscription_id' => $result['subscription']->id ?? null,
                            ]);
                        } else {
                            Log::error('Failed to process subscription payment via webhook', [
                                'reference' => $transaction['reference'],
                                'transaction_id' => $transaction['id'],
                                'error' => $result['error'] ?? 'Unknown error',
                            ]);
                        }
                    } elseif (in_array($transaction['status'], ['DECLINED', 'ERROR'])) {
                        Log::warning('Subscription payment failed via webhook', [
                            'reference' => $transaction['reference'],
                            'transaction_id' => $transaction['id'],
                            'status' => $transaction['status'],
                        ]);
                    }
                } else {
                    Log::info('Non-subscription transaction ignored', [
                        'reference' => $transaction['reference'],
                        'redirect_url' => $transaction['redirect_url'] ?? null,
                    ]);
                }
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Error processing Wompi webhook', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Get available financial institutions for PSE
     */
    public function getFinancialInstitutions()
    {
        $result = $this->wompiService->getFinancialInstitutions();

        return response()->json([
            'success' => $result['success'],
            'institutions' => $result['institutions'],
        ]);
    }

    /**
     * Check transaction status
     */
    public function checkTransactionStatus(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $result = $this->wompiService->getTransactionByReference($request->reference);

        if ($result['success'] && $result['transaction']) {
            return response()->json([
                'success' => true,
                'transaction' => $result['transaction'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Transaction not found',
        ], 404);
    }

    /**
     * Test Wompi API connection (temporary for debugging)
     */
    public function testWompiConnection()
    {
        try {
            $result = $this->wompiService->testConnection();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] ? 'Connection successful' : 'Connection failed',
                'details' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check subscription status (temporary for debugging)
     */
    public function checkSubscriptionStatus()
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return response()->json([
                    'error' => 'User not authenticated',
                ], 401);
            }

            $subscriptions = \App\Models\TenantSubscription::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('tenant_id'); // For new signups
            })->get();

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'tenant_id' => $user->tenant_id,
                    'roles' => $user->getRoleNames(),
                ],
                'subscriptions' => $subscriptions->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'user_id' => $sub->user_id,
                        'tenant_id' => $sub->tenant_id,
                        'plan_name' => $sub->plan_name,
                        'status' => $sub->status,
                        'expires_at' => $sub->expires_at,
                        'paid_at' => $sub->paid_at,
                        'payment_reference' => $sub->payment_reference,
                        'is_active' => $sub->status === 'active' && ($sub->expires_at === null || $sub->expires_at > now()),
                    ];
                }),
                'middleware_check' => $this->simulateMiddlewareCheck($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function simulateMiddlewareCheck($user)
    {
        $activeSubscription = \App\Models\TenantSubscription::where(function ($query) use ($user) {
            $query->whereNull('tenant_id') // For new signups
                ->orWhere('tenant_id', $user->tenant_id);
        })
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        return [
            'user_has_admin_role' => $user->hasRole('admin'),
            'user_tenant_id' => $user->tenant_id,
            'active_subscription_found' => ! is_null($activeSubscription),
            'subscription_details' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'user_id' => $activeSubscription->user_id,
                'tenant_id' => $activeSubscription->tenant_id,
                'status' => $activeSubscription->status,
                'expires_at' => $activeSubscription->expires_at,
            ] : null,
        ];
    }

    /**
     * Manual process payment for debugging
     */
    public function manualProcessPayment(Request $request)
    {
        try {
            $user = auth()->user();

            if (! $user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            // Create test transaction data to simulate successful payment
            $testTransaction = [
                'id' => 'test_transaction_'.time(),
                'status' => 'APPROVED',
                'reference' => 'SUB-NEW-PROFESIONAL-'.$user->id.'-'.time(),
                'amount_in_cents' => 19900000, // 199000 COP
                'payment_method_type' => 'CARD',
            ];

            // Create pending subscription data manually
            $subscriptionData = [
                'tenant_id' => null,
                'user_id' => $user->id,
                'plan_name' => 'PROFESIONAL',
                'amount' => 199000,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => null,
                'conjunto_name' => 'Test Conjunto',
                'conjunto_address' => null,
                'billing_type' => 'mensual',
                'city' => 'Bogotá',
                'region' => 'Bogotá D.C.',
            ];

            // Store pending data
            cache()->put(
                'pending_subscription_'.$testTransaction['reference'],
                $subscriptionData,
                now()->addHours(24)
            );

            // Process the payment
            $result = $this->wompiService->processSuccessfulSubscriptionPayment($testTransaction);

            return response()->json([
                'test_transaction' => $testTransaction,
                'subscription_data' => $subscriptionData,
                'processing_result' => $result,
                'cache_check' => cache()->get('pending_subscription_'.$testTransaction['reference']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Test the success flow with real payment parameters
     */
    public function testSuccessFlow(Request $request)
    {
        try {
            // Get user and create a realistic scenario
            $user = auth()->user();

            if (! $user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            // First, let's check if there are any cached pending subscriptions
            $cacheKeys = [];
            for ($i = 1; $i <= 10; $i++) {
                $testReference = "SUB-NEW-PROFESIONAL-{$user->id}-".(time() - ($i * 60));
                $cached = cache()->get('pending_subscription_'.$testReference);
                if ($cached) {
                    $cacheKeys[$testReference] = $cached;
                }
            }

            // Now let's test what happens if we manually hit the success URL
            // with typical Wompi parameters
            $testReference = $request->get('reference') ?? 'SUB-NEW-PROFESIONAL-'.$user->id.'-'.(time() - 300);
            $testTransactionId = $request->get('id') ?? 'test_tx_'.time();

            // Create pending data if it doesn't exist
            if (! cache()->has('pending_subscription_'.$testReference)) {
                $subscriptionData = [
                    'tenant_id' => null,
                    'user_id' => $user->id,
                    'plan_name' => 'PROFESIONAL',
                    'amount' => 199000,
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => null,
                    'conjunto_name' => 'Test Conjunto Real',
                    'conjunto_address' => null,
                    'billing_type' => 'mensual',
                    'city' => 'Bogotá',
                    'region' => 'Bogotá D.C.',
                ];

                cache()->put(
                    'pending_subscription_'.$testReference,
                    $subscriptionData,
                    now()->addHours(24)
                );
            }

            // Now simulate the success page visit
            $successUrl = route('subscription.success', [
                'reference' => $testReference,
                'id' => $testTransactionId,
            ]);

            return response()->json([
                'message' => 'Test scenario created',
                'user_id' => $user->id,
                'test_reference' => $testReference,
                'test_transaction_id' => $testTransactionId,
                'cached_pending_subscriptions' => $cacheKeys,
                'success_url_to_test' => $successUrl,
                'instructions' => 'Visit the success_url_to_test to simulate the real Wompi redirect',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
