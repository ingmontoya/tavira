<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Central\Provider;
use App\Models\QuotationRequest;
use App\Models\QuotationResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProviderQuotationController extends Controller
{
    private function getProvider(Request $request): Provider
    {
        $provider = Provider::find($request->user()->provider_id);
        if (!$provider) {
            abort(403, 'No tienes un perfil de proveedor asociado.');
        }
        return $provider;
    }

    /**
     * Display quotation requests available for the provider.
     */
    public function index(Request $request)
    {
        $provider = $this->getProvider($request);
        $provider->load('categories');
        $categoryIds = $provider->categories->pluck('id')->toArray();

        $allRequests = [];
        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($categoryIds, &$allRequests, $request, $tenant) {
                $query = QuotationRequest::published()
                    ->whereHas('categories', function ($q) use ($categoryIds) {
                        $q->whereIn('provider_category_id', $categoryIds);
                    })
                    ->with(['categories', 'createdBy']);

                // Filter by status if provided
                if ($request->status && $request->status !== 'all') {
                    if ($request->status === 'open') {
                        $query->where('deadline', '>=', now());
                    }
                }

                $requests = $query->latest()->get();

                foreach ($requests as $req) {
                    $allRequests[] = [
                        'id' => $req->id,
                        'title' => $req->title,
                        'description' => $req->description,
                        'deadline' => $req->deadline?->format('Y-m-d'),
                        'status' => $req->status,
                        'created_at' => $req->created_at->toISOString(),
                        'categories' => $req->categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name ?? $tenant->id,
                    ];
                }
            });
        }

        // Sort by created_at descending
        usort($allRequests, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Paginate manually
        $perPage = 15;
        $currentPage = $request->input('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedRequests = array_slice($allRequests, $offset, $perPage);

        return Inertia::render('Provider/Quotations/Index', [
            'requests' => [
                'data' => $paginatedRequests,
                'total' => count($allRequests),
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil(count($allRequests) / $perPage),
            ],
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Display a specific quotation request.
     */
    public function show(Request $request, $tenantId, $quotationRequestId)
    {
        $provider = $this->getProvider($request);

        // Find the tenant and run query in its context
        $tenant = \App\Models\Tenant::find($tenantId);

        if (!$tenant) {
            abort(404, 'Conjunto no encontrado.');
        }

        $data = $tenant->run(function () use ($quotationRequestId, $provider) {
            $quotationRequest = QuotationRequest::with(['categories', 'createdBy'])->findOrFail($quotationRequestId);

            // Check if provider already submitted a response (including soft deleted ones)
            $existingResponse = QuotationResponse::withTrashed()
                ->where('quotation_request_id', $quotationRequest->id)
                ->where('provider_id', $provider->id)
                ->first();

            // If there's a soft-deleted response, restore it instead of creating a new one
            if ($existingResponse && $existingResponse->trashed()) {
                $existingResponse->restore();
            }

            $canRespond = $quotationRequest->status === 'published' &&
                         (!$quotationRequest->deadline || $quotationRequest->deadline >= now()) &&
                         !$existingResponse;

            return [
                'request' => [
                    'id' => $quotationRequest->id,
                    'title' => $quotationRequest->title,
                    'description' => $quotationRequest->description,
                    'deadline' => $quotationRequest->deadline?->format('Y-m-d'),
                    'requirements' => $quotationRequest->requirements,
                    'status' => $quotationRequest->status,
                    'created_at' => $quotationRequest->created_at->toISOString(),
                    'categories' => $quotationRequest->categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]),
                    'created_by' => [
                        'name' => $quotationRequest->createdBy->name,
                        'email' => $quotationRequest->createdBy->email,
                    ],
                ],
                'existingResponse' => $existingResponse ? [
                    'id' => $existingResponse->id,
                    'quoted_amount' => $existingResponse->quoted_amount,
                    'proposal' => $existingResponse->proposal,
                    'estimated_days' => $existingResponse->estimated_days,
                    'status' => $existingResponse->status,
                    'created_at' => $existingResponse->created_at->toISOString(),
                ] : null,
                'canRespond' => $canRespond,
                'tenant_id' => tenancy()->tenant->id,
            ];
        });

        return Inertia::render('Provider/Quotations/Show', $data);
    }

    /**
     * Store a quotation response.
     */
    public function respond(Request $request, $tenantId, $quotationRequestId)
    {
        $provider = $this->getProvider($request);

        $validated = $request->validate([
            'quoted_amount' => 'required|numeric|min:0',
            'proposal' => 'nullable|string|max:2000',
            'estimated_days' => 'nullable|integer|min:0',
            'attachments' => 'nullable|array',
        ]);

        // Find the tenant and run query in its context
        $tenant = \App\Models\Tenant::find($tenantId);

        if (!$tenant) {
            abort(404, 'Conjunto no encontrado.');
        }

        try {
            $tenant->run(function () use ($quotationRequestId, $provider, $validated) {
                $quotationRequest = QuotationRequest::findOrFail($quotationRequestId);

                if ($quotationRequest->status !== 'published') {
                    throw new \Exception('Esta solicitud de cotización ya está cerrada.');
                }

                if ($quotationRequest->deadline && $quotationRequest->deadline < now()) {
                    throw new \Exception('El plazo para responder ha expirado.');
                }

                // Use withTrashed to handle soft-deleted responses
                $response = QuotationResponse::withTrashed()->updateOrCreate(
                    [
                        'quotation_request_id' => $quotationRequest->id,
                        'provider_id' => $provider->id,
                    ],
                    [
                        'quoted_amount' => $validated['quoted_amount'],
                        'proposal' => $validated['proposal'] ?? null,
                        'estimated_days' => $validated['estimated_days'] ?? null,
                        'attachments' => $validated['attachments'] ?? null,
                        'status' => 'pending',
                        'deleted_at' => null, // Restore if it was soft deleted
                    ]
                );

                Log::info('Quotation response submitted', [
                    'response_id' => $response->id,
                    'provider_id' => $provider->id,
                    'quotation_request_id' => $quotationRequest->id,
                    'tenant_id' => tenancy()->tenant->id,
                ]);
            });

            return redirect()->route('provider.quotations.index')
                ->with('success', 'Propuesta enviada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation response failed', [
                'error' => $e->getMessage(),
                'provider_id' => $provider->id,
                'tenant_id' => $tenantId,
            ]);

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display provider's proposals history.
     */
    public function proposals(Request $request)
    {
        $provider = $this->getProvider($request);

        $query = QuotationResponse::with(['quotationRequest.conjunto'])
            ->where('provider_id', $provider->id);

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $proposals = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Provider/Quotations/Proposals', [
            'proposals' => $proposals,
            'filters' => $request->only(['status']),
        ]);
    }
}
