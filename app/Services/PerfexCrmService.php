<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PerfexCrmService
{
    private string $baseUrl;

    private string $apiUser;

    private ?string $apiToken;

    public function __construct()
    {
        $this->baseUrl = config('services.perfex.base_url') ?? 'https://perfexcrm.themesic.com';
        $this->apiUser = config('services.perfex.api_user') ?? 'precontactos';
        $this->apiToken = config('services.perfex.api_token');
    }

    /**
     * Create a new lead in Perfex CRM
     */
    public function createLead(array $leadData): array
    {
        try {
            // Token must be sent as HTTP header with name 'authtoken'
            $endpoint = "{$this->baseUrl}/api/leads";

            // Prepare the payload according to Perfex CRM API requirements
            $payload = $this->prepareLeadData($leadData);

            Log::info('Creating Perfex CRM lead', [
                'endpoint' => $endpoint,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'authtoken' => $this->apiToken,
            ])->asForm()->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info('Perfex CRM lead created successfully', [
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Lead created successfully',
                ];
            }

            Log::error('Perfex CRM API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $response->body(),
                'message' => 'Failed to create lead',
            ];
        } catch (\Exception $e) {
            Log::error('Exception creating Perfex CRM lead', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Exception occurred while creating lead',
            ];
        }
    }

    /**
     * Prepare lead data according to Perfex CRM API format
     */
    private function prepareLeadData(array $data): array
    {
        // Required fields for Perfex CRM
        $payload = [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phonenumber' => $data['phone'] ?? '',
            'status' => $data['status'] ?? 2, // 2 = "Contactado"
            'assigned' => $data['assigned'] ?? 1, // 1 = Usuario asignado por defecto
            'source' => $data['source'] ?? 3, // 3 = "Web"
        ];

        // Optional fields
        if (! empty($data['company'])) {
            $payload['company'] = $data['company'];
        }

        if (! empty($data['title'])) {
            $payload['title'] = $data['title'];
        }

        if (! empty($data['website'])) {
            $payload['website'] = $data['website'];
        }

        if (! empty($data['address'])) {
            $payload['address'] = $data['address'];
        }

        if (! empty($data['city'])) {
            $payload['city'] = $data['city'];
        }

        if (! empty($data['state'])) {
            $payload['state'] = $data['state'];
        }

        if (! empty($data['country'])) {
            $payload['country'] = $data['country'];
        }

        if (! empty($data['zip'])) {
            $payload['zip'] = $data['zip'];
        }

        if (! empty($data['description'])) {
            $payload['description'] = $data['description'];
        }

        // Tags
        if (! empty($data['tags'])) {
            $payload['tags'] = is_array($data['tags'])
                ? implode(',', $data['tags'])
                : $data['tags'];
        }

        // Custom fields for conjunto information
        if (! empty($data['conjunto_name'])) {
            $payload['custom_field_conjunto_name'] = $data['conjunto_name'];
        }

        if (! empty($data['num_units'])) {
            $payload['custom_field_num_units'] = $data['num_units'];
        }

        // Send the selected role from the form (custom field ID 1)
        if (! empty($data['role'])) {
            $payload['custom_fields[leads][1]'] = $data['role'];
        }

        // Additional parameters for lead creation behavior
        $payload['blockcreatelead'] = $data['blockcreatelead'] ?? 0; // Allow duplicate emails by default
        $payload['blockupdatelead'] = $data['blockupdatelead'] ?? 0; // Allow updates by default

        return $payload;
    }

    /**
     * Get lead by email
     */
    public function getLeadByEmail(string $email): ?array
    {
        try {
            $endpoint = "{$this->baseUrl}/api/leads";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($endpoint, [
                'token' => $this->apiToken,
                'email' => $email,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['leads'][0] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Exception getting Perfex CRM lead', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update existing lead
     */
    public function updateLead(int $leadId, array $leadData): array
    {
        try {
            $endpoint = "{$this->baseUrl}/api/leads/{$leadId}";

            $payload = array_merge(
                ['token' => $this->apiToken],
                $leadData
            );

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->put($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Lead updated successfully',
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
                'message' => 'Failed to update lead',
            ];
        } catch (\Exception $e) {
            Log::error('Exception updating Perfex CRM lead', [
                'lead_id' => $leadId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Exception occurred while updating lead',
            ];
        }
    }

    /**
     * Get available lead sources
     */
    public function getLeadSources(): array
    {
        try {
            $endpoint = "{$this->baseUrl}/api/leads/sources";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($endpoint, [
                'token' => $this->apiToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting Perfex CRM lead sources', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get available lead statuses
     */
    public function getLeadStatuses(): array
    {
        try {
            $endpoint = "{$this->baseUrl}/api/leads/statuses";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get($endpoint, [
                'token' => $this->apiToken,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Exception getting Perfex CRM lead statuses', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
