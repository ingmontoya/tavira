<?php

namespace App\Services;

use Bancolombia\Wompi;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class WompiPaymentService
{
    public function __construct()
    {
        $publicKey = config('laravel-wompi.public_key');
        $privateKey = config('laravel-wompi.private_key');
        $eventKey = config('laravel-wompi.private_event_key');

        // Log key status for debugging (without exposing actual keys)
        Log::info('Wompi keys configuration check:', [
            'public_key_set' => !empty($publicKey),
            'private_key_set' => !empty($privateKey),
            'event_key_set' => !empty($eventKey),
            'public_key_type' => $publicKey ? (str_contains($publicKey, 'test') ? 'test' : 'prod') : 'none',
            'environment' => config('app.env')
        ]);

        // Initialize Wompi with configuration from Laravel config
        Wompi::initialize([
            'public_key' => $publicKey,
            'private_key' => $privateKey,
            'private_event_key' => $eventKey
        ]);
    }

    /**
     * Get the acceptance token required for payments
     */
    public function getAcceptanceToken()
    {
        try {
            $response = Wompi::acceptance_token();
            
            // Convert stdClass to array if necessary
            $responseArray = json_decode(json_encode($response), true);
            
            if ($responseArray && isset($responseArray['data']['presigned_acceptance']['acceptance_token'])) {
                return $responseArray['data']['presigned_acceptance']['acceptance_token'];
            }
            
            Log::error('Invalid acceptance token response:', [
                'response_type' => gettype($response),
                'response' => $responseArray
            ]);
            
            throw new Exception('Unable to get acceptance token from Wompi');
        } catch (Exception $e) {
            Log::error('Error getting Wompi acceptance token', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create a payment link for Tavira subscription plan
     */
    public function createSubscriptionPaymentLink(array $subscriptionData, array $options = [])
    {
        try {
            // Validate required data first
            $this->validateSubscriptionData($subscriptionData);
            
            $reference = $this->generateUniqueSubscriptionReference($subscriptionData);
            
            $data = [
                'name' => "Suscripción Tavira - {$subscriptionData['plan_name']}",
                'description' => "Suscripción al plan {$subscriptionData['plan_name']} de Tavira para {$subscriptionData['conjunto_name']}",
                'single_use' => false,
                'collect_shipping' => false,
                'currency' => 'COP',
                'amount_in_cents' => intval($subscriptionData['amount'] * 100),
                'reference' => $reference,
                'customer_email' => $subscriptionData['customer_email'],
                'payment_methods' => $options['payment_methods'] ?? ['CARD', 'NEQUI', 'PSE'],
                'redirect_url' => $options['redirect_url'] ?? route('subscription.success'),
                'collect_phone_number' => true,
                'shipping_address' => [
                    'address_line_1' => $subscriptionData['conjunto_address'] ?? 'N/A',
                    'country' => 'CO',
                    'region' => $options['region'] ?? 'Bogotá D.C.',
                    'city' => $options['city'] ?? 'Bogotá',
                    'name' => $subscriptionData['customer_name'],
                    'phone_number' => $subscriptionData['customer_phone'] ?? '',
                ],
                'expiration_time' => $options['expiration_hours'] ?? 72, // 72 hours for subscription
                'tax_in_cents' => [
                    [
                        'type' => 'VAT',
                        'amount_in_cents' => intval($subscriptionData['amount'] * 100 * 0.19), // 19% IVA
                    ]
                ]
            ];

            Log::info('Creating Wompi payment link with data:', [
                'reference' => $reference,
                'amount' => $data['amount_in_cents'],
                'customer_email' => $data['customer_email']
            ]);

            $response = Wompi::link($data);
            
            // Convert stdClass to array if necessary
            $responseArray = json_decode(json_encode($response), true);
            
            Log::info('Wompi API response:', [
                'response_type' => gettype($response),
                'response' => $responseArray,
                'has_data' => isset($responseArray['data']),
                'has_id' => isset($responseArray['data']['id']) ?? false
            ]);
            
            if ($responseArray && isset($responseArray['data']['id'])) {
                // Store pending subscription data for later processing
                $this->storePendingSubscription($reference, $subscriptionData);

                return [
                    'success' => true,
                    'payment_link' => $responseArray['data']['permalink'],
                    'payment_link_id' => $responseArray['data']['id'],
                    'reference' => $reference,
                    'data' => $responseArray['data']
                ];
            }

            // Log the full response for debugging
            Log::error('Invalid Wompi response structure:', [
                'response' => $responseArray,
                'response_type' => gettype($response),
                'expected_fields' => ['data.id', 'data.permalink']
            ]);

            throw new Exception('Invalid response from Wompi payment link creation: ' . json_encode($responseArray));
        } catch (Exception $e) {
            Log::error('Error creating Wompi subscription payment link', [
                'subscription_data' => $subscriptionData,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a direct payment transaction
     */
    public function createTransaction(array $paymentData, string $acceptanceToken)
    {
        try {
            $transactionData = [
                'amount_in_cents' => intval($paymentData['amount'] * 100),
                'currency' => 'COP',
                'reference' => $paymentData['reference'],
                'customer_email' => $paymentData['customer_email'],
                'payment_method' => [
                    'type' => $paymentData['payment_type'], // CARD, NEQUI, PSE, etc.
                    'installments' => $paymentData['installments'] ?? 1,
                ],
                'customer_data' => [
                    'phone_number' => $paymentData['phone_number'] ?? '',
                    'full_name' => $paymentData['customer_name'] ?? '',
                ],
                'shipping_address' => [
                    'address_line_1' => $paymentData['address'] ?? '',
                    'country' => 'CO',
                    'city' => $paymentData['city'] ?? 'Bogotá',
                    'phone_number' => $paymentData['phone_number'] ?? '',
                ]
            ];

            // Add payment method specific data
            if ($paymentData['payment_type'] === 'CARD') {
                $transactionData['payment_method']['token'] = $paymentData['card_token'];
            } elseif ($paymentData['payment_type'] === 'NEQUI') {
                $transactionData['payment_method']['phone_number'] = $paymentData['nequi_phone'];
            } elseif ($paymentData['payment_type'] === 'PSE') {
                $transactionData['payment_method'] = array_merge($transactionData['payment_method'], [
                    'user_type' => $paymentData['user_type'] ?? 0, // 0 = natural, 1 = juridica
                    'user_legal_id_type' => $paymentData['user_legal_id_type'] ?? 'CC',
                    'user_legal_id' => $paymentData['user_legal_id'],
                    'financial_institution_code' => $paymentData['financial_institution_code'],
                    'payment_description' => $paymentData['payment_description'] ?? 'Pago factura'
                ]);
            }

            $response = Wompi::transaction($transactionData);
            
            return [
                'success' => isset($response['data']['id']),
                'transaction_id' => $response['data']['id'] ?? null,
                'status' => $response['data']['status'] ?? null,
                'reference' => $response['data']['reference'] ?? null,
                'data' => $response
            ];

        } catch (Exception $e) {
            Log::error('Error creating Wompi transaction', [
                'error' => $e->getMessage(),
                'payment_data' => $paymentData
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check transaction status by reference
     */
    public function getTransactionByReference(string $reference)
    {
        try {
            $response = Wompi::find_transaction($reference);
            
            return [
                'success' => isset($response['data']),
                'transaction' => $response['data'] ?? null
            ];
        } catch (Exception $e) {
            Log::error('Error getting transaction by reference', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get transaction by ID
     */
    public function getTransactionById(string $transactionId)
    {
        try {
            $response = Wompi::transaction_find_by_id($transactionId);
            
            return [
                'success' => isset($response['data']),
                'transaction' => $response['data'] ?? null
            ];
        } catch (Exception $e) {
            Log::error('Error getting transaction by ID', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get PSE financial institutions
     */
    public function getFinancialInstitutions()
    {
        try {
            $response = Wompi::financial_institutions();
            
            return [
                'success' => isset($response['data']),
                'institutions' => $response['data'] ?? []
            ];
        } catch (Exception $e) {
            Log::error('Error getting financial institutions', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'institutions' => []
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($request)
    {
        try {
            return Wompi::check_webhook($request);
        } catch (Exception $e) {
            Log::error('Error verifying webhook signature', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process a successful subscription payment from webhook
     */
    public function processSuccessfulSubscriptionPayment(array $transactionData)
    {
        try {
            // Extract subscription info from reference
            $referenceInfo = $this->parseSubscriptionReference($transactionData['reference']);
            
            if (!$referenceInfo) {
                throw new Exception('Invalid subscription reference format');
            }

            // Get stored pending subscription data
            $pendingData = $this->getPendingSubscriptionData($transactionData['reference']);

            // Find or create tenant subscription record
            $subscription = \App\Models\TenantSubscription::updateOrCreate(
                [
                    'payment_reference' => $transactionData['reference'],
                ],
                [
                    'tenant_id' => $referenceInfo['tenant_id'],
                    'user_id' => $pendingData['user_id'] ?? null,
                    'plan_name' => $referenceInfo['plan_name'],
                    'amount' => $transactionData['amount_in_cents'] / 100,
                    'payment_method' => 'wompi_' . strtolower($transactionData['payment_method_type']),
                    'payment_reference' => $transactionData['reference'],
                    'transaction_id' => $transactionData['id'],
                    'status' => 'active',
                    'paid_at' => now(),
                    'expires_at' => $this->calculateSubscriptionExpiry($referenceInfo['plan_name']),
                    'payment_data' => $pendingData ?: $transactionData,
                ]
            );

            // Activate tenant if not already active
            $tenant = null;
            if ($referenceInfo['tenant_id']) {
                $tenant = \App\Models\Tenant::find($referenceInfo['tenant_id']);
                if ($tenant) {
                    $tenant->update([
                        'subscription_status' => 'active',
                        'subscription_plan' => $referenceInfo['plan_name'],
                        'subscription_expires_at' => $subscription->expires_at,
                    ]);
                }
            }

            Log::info('Subscription payment processed successfully', [
                'tenant_id' => $referenceInfo['tenant_id'],
                'transaction_id' => $transactionData['id'],
                'subscription_id' => $subscription->id
            ]);

            return [
                'success' => true,
                'subscription' => $subscription,
                'tenant' => $tenant
            ];

        } catch (Exception $e) {
            Log::error('Error processing successful subscription payment', [
                'transaction_data' => $transactionData,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate a unique reference for subscription payment
     */
    private function generateUniqueSubscriptionReference(array $subscriptionData): string
    {
        $tenantId = $subscriptionData['tenant_id'] ?? 'NEW';
        $planName = strtoupper($subscriptionData['plan_name']);
        return 'SUB-' . $tenantId . '-' . $planName . '-' . time() . '-' . Str::random(4);
    }

    /**
     * Parse subscription reference to extract info
     */
    private function parseSubscriptionReference(string $reference): ?array
    {
        // Format: SUB-{tenant_id}-{plan_name}-{timestamp}-{random}
        $parts = explode('-', $reference);
        
        if (count($parts) < 4 || $parts[0] !== 'SUB') {
            return null;
        }
        
        return [
            'tenant_id' => $parts[1] !== 'NEW' ? $parts[1] : null,
            'plan_name' => $parts[2],
            'timestamp' => $parts[3] ?? null,
            'random' => $parts[4] ?? null,
        ];
    }

    /**
     * Calculate subscription expiry based on plan
     */
    private function calculateSubscriptionExpiry(string $planName): \Carbon\Carbon
    {
        $planDurations = [
            'BASICO' => 1, // 1 month
            'PROFESIONAL' => 1, // 1 month
            'EMPRESARIAL' => 1, // 1 month
            'ANUAL_BASICO' => 12, // 12 months
            'ANUAL_PROFESIONAL' => 12, // 12 months
            'ANUAL_EMPRESARIAL' => 12, // 12 months
        ];
        
        $duration = $planDurations[$planName] ?? 1;
        return now()->addMonths($duration);
    }

    /**
     * Generate a unique payment number
     */
    private function generatePaymentNumber(): string
    {
        $lastPayment = Payment::orderBy('id', 'desc')->first();
        $nextNumber = $lastPayment ? intval(substr($lastPayment->payment_number, 2)) + 1 : 1;
        
        return 'PG' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Store pending subscription data for later processing
     */
    private function storePendingSubscription(string $reference, array $subscriptionData): void
    {
        try {
            // Use cache to store temporary subscription data
            cache()->put(
                'pending_subscription_' . $reference,
                $subscriptionData,
                now()->addHours(24) // Store for 24 hours
            );
        } catch (Exception $e) {
            Log::warning('Could not store pending subscription data', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get pending subscription data
     */
    private function getPendingSubscriptionData(string $reference): ?array
    {
        try {
            return cache()->get('pending_subscription_' . $reference);
        } catch (Exception $e) {
            Log::warning('Could not retrieve pending subscription data', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate subscription data before creating payment link
     */
    private function validateSubscriptionData(array $data)
    {
        $required = ['plan_name', 'amount', 'customer_name', 'customer_email', 'conjunto_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format: {$data['customer_email']}");
        }

        if ($data['amount'] <= 0) {
            throw new Exception("Invalid amount: {$data['amount']}");
        }

        Log::info('Subscription data validation passed', [
            'plan_name' => $data['plan_name'],
            'amount' => $data['amount'],
            'customer_email' => $data['customer_email']
        ]);
    }

    /**
     * Test Wompi API connection
     */
    public function testConnection()
    {
        try {
            Log::info('Testing Wompi API connection...');
            
            // Try to get acceptance token - this is a simple API call to test connectivity
            $response = Wompi::acceptance_token();
            
            // Convert stdClass to array if necessary
            $responseArray = json_decode(json_encode($response), true);
            
            Log::info('Wompi connection test result:', [
                'success' => !empty($responseArray),
                'response_type' => gettype($response),
                'has_data' => isset($responseArray['data']),
                'response_keys' => $responseArray ? array_keys($responseArray) : []
            ]);

            return [
                'success' => !empty($responseArray),
                'response' => $responseArray
            ];
        } catch (Exception $e) {
            Log::error('Wompi connection test failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}