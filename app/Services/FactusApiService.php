<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FactusApiService
{
    private string $baseUrl;

    private string $email;

    private string $password;

    private string $clientId;

    private string $clientSecret;

    private ?string $accessToken = null;

    private ?string $refreshToken = null;

    public function __construct(array $config = [])
    {
        $this->baseUrl = $config['base_url'] ?? 'https://api-sandbox.factus.com.co';
        $this->email = $config['email'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
    }

    /**
     * Set configuration for the service
     */
    public function setConfig(array $config): self
    {
        $this->baseUrl = $config['base_url'] ?? $this->baseUrl;
        $this->email = $config['email'] ?? $this->email;
        $this->password = $config['password'] ?? $this->password;
        $this->clientId = $config['client_id'] ?? $this->clientId;
        $this->clientSecret = $config['client_secret'] ?? $this->clientSecret;

        return $this;
    }

    /**
     * Authenticate with Factus API
     */
    public function authenticate(): array
    {
        try {
            $response = Http::post($this->baseUrl.'/oauth/token', [
                'grant_type' => 'password',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->email,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->refreshToken = $data['refresh_token'] ?? null;

                return [
                    'success' => true,
                    'message' => 'Autenticación exitosa',
                    'data' => $data,
                ];
            }

            Log::error('Factus authentication failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de autenticación: '.$response->body(),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('Factus authentication exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Test connection to Factus API
     */
    public function testConnection(): array
    {
        $authResult = $this->authenticate();

        if (! $authResult['success']) {
            return $authResult;
        }

        // If authentication was successful, the connection is working
        // We don't need to make another request since OAuth authentication
        // already validates the connection and credentials
        return [
            'success' => true,
            'message' => 'Conexión exitosa con Factus - Autenticación completada',
            'data' => $authResult['data'],
        ];
    }

    /**
     * Make an authenticated request to the API
     */
    private function makeAuthenticatedRequest(string $method, string $endpoint, array $data = []): Response
    {
        if (! $this->accessToken) {
            $authResult = $this->authenticate();
            if (! $authResult['success']) {
                throw new \Exception('No se pudo autenticar con Factus API');
            }
        }

        $url = $this->baseUrl.$endpoint;
        $headers = [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        return Http::withHeaders($headers)->$method($url, $data);
    }

    /**
     * Test authenticated request with common endpoints
     */
    public function testAuthenticatedEndpoint(): array
    {
        if (! $this->accessToken) {
            $authResult = $this->authenticate();
            if (! $authResult['success']) {
                return $authResult;
            }
        }

        // Try common endpoints that might exist
        $testEndpoints = [
            '/api/v1/user',
            '/api/user',
            '/api/profile',
            '/api/me',
            '/api/account',
            '/api/companies',
            '/api/v1/companies',
        ];

        foreach ($testEndpoints as $endpoint) {
            try {
                $response = $this->makeAuthenticatedRequest('GET', $endpoint);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'message' => "Conexión exitosa con Factus - Endpoint disponible: {$endpoint}",
                        'data' => $response->json(),
                        'endpoint' => $endpoint,
                    ];
                }
            } catch (\Exception $e) {
                // Continue to next endpoint
                continue;
            }
        }

        // If no endpoints work, authentication is still valid
        return [
            'success' => true,
            'message' => 'Autenticación exitosa - No se encontraron endpoints de prueba específicos',
            'note' => 'La autenticación funciona correctamente. Los endpoints específicos pueden requerir documentación adicional.',
        ];
    }

    /**
     * Send invoice to Factus using the correct API endpoint and structure
     */
    public function sendInvoice(array $invoiceData): array
    {
        try {
            // Use the correct Factus endpoint for creating and validating invoices
            $response = $this->makeAuthenticatedRequest('POST', '/v1/bills/validate', $invoiceData);

            if ($response->successful()) {
                $responseData = $response->json();

                // Log successful response for debugging
                Log::info('Factus invoice sent successfully', [
                    'response_data' => $responseData,
                ]);

                return [
                    'success' => true,
                    'message' => 'Factura enviada y validada exitosamente',
                    'data' => $responseData,
                    'invoice_id' => $responseData['data']['bill']['id'] ?? $responseData['data']['id'] ?? $responseData['id'] ?? null,
                    'uuid' => $responseData['data']['bill']['uuid'] ?? $responseData['data']['uuid'] ?? $responseData['uuid'] ?? null,
                    'cufe' => $responseData['data']['bill']['cufe'] ?? $responseData['data']['cufe'] ?? $responseData['cufe'] ?? null,
                    'factus_id' => $responseData['data']['bill']['id'] ?? $responseData['data']['id'] ?? $responseData['id'] ?? null,
                    'public_url' => $responseData['data']['bill']['public_url'] ?? $responseData['data']['public_url'] ?? $responseData['public_url'] ?? null,
                    'bill' => $responseData['data']['bill'] ?? null, // Include the full bill object for controller access
                    'factus_response' => $responseData, // Store full response for debugging
                ];
            }

            // Handle specific Factus error responses
            $errorData = $response->json();
            $errorMessage = 'Error al enviar factura';

            if ($response->status() === 409) {
                $errorMessage = 'Ya existe una factura pendiente con este código de referencia';
            } elseif ($response->status() === 422) {
                $errorMessage = 'Error de validación: '.($errorData['message'] ?? 'Datos inválidos');
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            }

            Log::error('Factus invoice send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'invoice_data' => $invoiceData,
                'response_headers' => $response->headers(),
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $response->status(),
                'errors' => $errorData['errors'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Factus invoice send exception', [
                'message' => $e->getMessage(),
                'invoice_data' => $invoiceData,
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar factura: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice status from Factus
     */
    public function getInvoiceStatus(string $invoiceId): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', "/v1/bills/{$invoiceId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al obtener estado de factura: '.$response->body(),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener estado de factura: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get numbering ranges from Factus
     */
    public function getNumberingRanges(): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', '/v1/numbering-ranges');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al obtener rangos de numeración: '.$response->body(),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener rangos de numeración: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Search for invoice by reference code
     */
    public function searchInvoiceByReference(string $referenceCode): array
    {
        try {
            if (! $this->accessToken) {
                $authResult = $this->authenticate();
                if (! $authResult['success']) {
                    return $authResult;
                }
            }

            $url = $this->baseUrl.'/v1/bills?'.http_build_query([
                'filter[reference_code]' => $referenceCode,
            ]);

            $headers = [
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al buscar factura: '.$response->body(),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar factura: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get electronic invoice PDF from Factus
     */
    public function getInvoicePdf(string $invoiceNumber): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', "/v1/bills/download-pdf/{$invoiceNumber}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->body(), // PDF content
                    'headers' => $response->headers(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al obtener PDF de factura: '.$response->body(),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener PDF de factura: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get electronic invoice XML from Factus
     */
    public function getInvoiceXml(string $invoiceId): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('GET', "/v1/bills/{$invoiceId}/xml");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->body(), // XML content
                    'headers' => $response->headers(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al obtener XML de factura: '.$response->body(),
                'status_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener XML de factura: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(): array
    {
        if (! $this->refreshToken) {
            return $this->authenticate();
        }

        try {
            $response = Http::post($this->baseUrl.'/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                $this->refreshToken = $data['refresh_token'] ?? $this->refreshToken;

                return [
                    'success' => true,
                    'message' => 'Token renovado exitosamente',
                    'data' => $data,
                ];
            }

            // If refresh fails, try full authentication
            return $this->authenticate();

        } catch (\Exception $e) {
            return $this->authenticate();
        }
    }

    /**
     * Get access token (for external use)
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * Set access token (for external use)
     */
    public function setAccessToken(?string $token): self
    {
        $this->accessToken = $token;

        return $this;
    }
}
