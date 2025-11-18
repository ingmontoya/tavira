<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Central\Provider;
use App\Models\Central\ProviderService;
use App\Models\QuotationRequest;
use App\Models\QuotationResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProviderDashboardController extends Controller
{
    /**
     * Display the provider dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $provider = Provider::find($user->provider_id);

        if (! $provider) {
            abort(403, 'No tienes un perfil de proveedor asociado.');
        }

        // Get provider's category IDs
        $provider->load('categories');
        $categoryIds = $provider->categories->pluck('id')->toArray();

        // Collect data from all tenants
        $recentRequests = [];
        $recentProposals = [];
        $totalProposals = 0;
        $pendingRequests = 0;

        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($categoryIds, &$recentRequests, &$recentProposals, &$totalProposals, &$pendingRequests, $provider, $tenant) {
                // Get published quotation requests matching provider's categories
                // Exclude requests where this provider has already submitted a response
                $requests = QuotationRequest::published()
                    ->whereHas('categories', function ($query) use ($categoryIds) {
                        $query->whereIn('provider_category_id', $categoryIds);
                    })
                    ->whereDoesntHave('responses', function ($query) use ($provider) {
                        $query->where('provider_id', $provider->id);
                    })
                    ->with(['categories', 'createdBy'])
                    ->latest()
                    ->limit(5)
                    ->get();

                foreach ($requests as $request) {
                    $recentRequests[] = [
                        'id' => $request->id,
                        'title' => $request->title,
                        'description' => $request->description,
                        'deadline' => $request->deadline?->format('Y-m-d'),
                        'status' => $request->status,
                        'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                        'categories' => $request->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
                        'tenant_id' => tenancy()->tenant->id ?? null,
                    ];
                }

                // Count statistics - only count requests where provider hasn't responded
                $pendingRequests += QuotationRequest::published()
                    ->whereHas('categories', function ($query) use ($categoryIds) {
                        $query->whereIn('provider_category_id', $categoryIds);
                    })
                    ->whereDoesntHave('responses', function ($query) use ($provider) {
                        $query->where('provider_id', $provider->id);
                    })
                    ->count();

                $totalProposals += QuotationResponse::where('provider_id', $provider->id)->count();

                // Get recent proposals from this tenant
                $proposals = QuotationResponse::where('provider_id', $provider->id)
                    ->with(['quotationRequest'])
                    ->latest()
                    ->limit(5)
                    ->get();

                foreach ($proposals as $proposal) {
                    $recentProposals[] = [
                        'id' => $proposal->id,
                        'quoted_amount' => $proposal->quoted_amount,
                        'proposal' => $proposal->proposal,
                        'estimated_days' => $proposal->estimated_days,
                        'status' => $proposal->status,
                        'created_at' => $proposal->created_at->format('Y-m-d H:i:s'),
                        'quotation_request' => [
                            'id' => $proposal->quotationRequest->id,
                            'title' => $proposal->quotationRequest->title,
                        ],
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name ?? $tenant->id,
                    ];
                }
            });
        }

        // Sort requests by created_at and take only the 5 most recent
        usort($recentRequests, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $recentRequests = array_slice($recentRequests, 0, 5);

        // Sort proposals by created_at and take only the 5 most recent
        usort($recentProposals, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $recentProposals = array_slice($recentProposals, 0, 5);

        // Get statistics
        $stats = [
            'total_services' => ProviderService::byProvider($provider->id)->count(),
            'active_services' => ProviderService::byProvider($provider->id)->active()->count(),
            'total_proposals' => $totalProposals,
            'pending_requests' => $pendingRequests,
        ];

        return Inertia::render('Provider/Dashboard', [
            'provider' => array_merge($provider->toArray(), [
                'subscription_plan' => $provider->subscription_plan,
                'leads_used_this_month' => $provider->leads_used_this_month,
                'leads_remaining' => $provider->leads_remaining,
                'has_seen_pricing' => $provider->has_seen_pricing,
            ]),
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'recentProposals' => $recentProposals,
            'proposalsTrend' => [],
        ]);
    }
}
