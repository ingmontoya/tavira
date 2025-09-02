<?php

namespace App\Http\Controllers;

use App\Services\WompiPaymentService;
use App\Models\Tenant;
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
        $plans = [
            [
                'id' => 'basico',
                'name' => 'Básico',
                'price' => 99000,
                'billing' => 'mensual',
                'features' => [
                    'Hasta 50 apartamentos',
                    'Gestión básica de residentes',
                    'Facturas y pagos básicos',
                    'Soporte por email',
                ],
                'popular' => false,
            ],
            [
                'id' => 'profesional',
                'name' => 'Profesional',
                'price' => 199000,
                'billing' => 'mensual',
                'features' => [
                    'Hasta 200 apartamentos',
                    'Gestión completa de residentes',
                    'Sistema de facturas avanzado',
                    'Contabilidad integrada',
                    'Reportes avanzados',
                    'Soporte prioritario',
                ],
                'popular' => true,
            ],
            [
                'id' => 'empresarial',
                'name' => 'Empresarial',
                'price' => 299000,
                'billing' => 'mensual',
                'features' => [
                    'Apartamentos ilimitados',
                    'Todas las funcionalidades',
                    'API personalizada',
                    'Integración con terceros',
                    'Soporte dedicado 24/7',
                    'Backup diario automático',
                ],
                'popular' => false,
            ],
        ];

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
        ]);
    }

    /**
     * Create payment link for subscription
     */
    public function createPaymentLink(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|in:basico,profesional,empresarial',
            'tenant_id' => 'nullable|exists:tenants,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone' => 'nullable|string|max:20',
            'conjunto_name' => 'required|string|max:255',
            'conjunto_address' => 'nullable|string|max:500',
            'billing_type' => 'required|in:mensual,anual',
        ]);

        try {
            // Define plan prices
            $planPrices = [
                'basico' => ['mensual' => 99000, 'anual' => 990000],
                'profesional' => ['mensual' => 199000, 'anual' => 1990000],
                'empresarial' => ['mensual' => 299000, 'anual' => 2990000],
            ];

            $planNames = [
                'basico' => 'Básico',
                'profesional' => 'Profesional',
                'empresarial' => 'Empresarial',
            ];

            $planId = $request->plan_id;
            $billingType = $request->billing_type;
            $amount = $planPrices[$planId][$billingType];
            $planName = ($billingType === 'anual' ? 'ANUAL_' : '') . strtoupper($planId);

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
                'message' => $result['error'] ?? 'Error creating payment link'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error creating subscription payment link', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
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
                    // Process the successful payment
                    $paymentResult = $this->wompiService->processSuccessfulSubscriptionPayment($transaction);
                    
                    if ($paymentResult['success']) {
                        return Inertia::render('Subscription/Success', [
                            'transaction' => $transaction,
                            'subscription' => $paymentResult['subscription'],
                            'tenant' => $paymentResult['tenant'],
                        ]);
                    }
                }
            }
        }

        return Inertia::render('Subscription/Success', [
            'transaction' => null,
            'message' => 'Verificando estado del pago...',
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
            'request' => $request->all()
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
            if (!$this->wompiService->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature from Wompi');
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            $event = $request->input('event');
            $data = $request->input('data');

            if ($event === 'transaction.updated' && isset($data['transaction'])) {
                $transaction = $data['transaction'];
                
                // Only process subscription payments
                if (str_starts_with($transaction['reference'], 'SUB-')) {
                    if ($transaction['status'] === 'APPROVED') {
                        $result = $this->wompiService->processSuccessfulSubscriptionPayment($transaction);
                        
                        if ($result['success']) {
                            Log::info('Subscription payment processed via webhook', [
                                'reference' => $transaction['reference'],
                                'transaction_id' => $transaction['id']
                            ]);
                        }
                    } elseif (in_array($transaction['status'], ['DECLINED', 'ERROR'])) {
                        Log::warning('Subscription payment failed via webhook', [
                            'reference' => $transaction['reference'],
                            'transaction_id' => $transaction['id'],
                            'status' => $transaction['status']
                        ]);
                    }
                }
            }

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Error processing Wompi webhook', [
                'error' => $e->getMessage(),
                'request' => $request->all()
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
            'institutions' => $result['institutions']
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
                'transaction' => $result['transaction']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Transaction not found'
        ], 404);
    }
}