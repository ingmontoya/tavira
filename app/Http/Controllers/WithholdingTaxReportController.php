<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Expense;
use App\Models\Provider;
use App\Models\WithholdingCertificate;
use App\Services\WithholdingCertificateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WithholdingTaxReportController extends Controller
{
    public function __construct(
        private WithholdingCertificateService $certificateService
    ) {}

    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::first();

        // Filtros de fecha
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Build query for expenses with retention
        $query = Expense::forConjunto($conjunto->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->with(['provider', 'taxAccount', 'expenseCategory']);

        // Filter by retention type (tax_account_id)
        if ($request->filled('retention_type')) {
            $query->where('tax_account_id', $request->retention_type);
        }

        // Filter by provider
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Filter by provider name (search)
        if ($request->filled('provider_search')) {
            $query->where(function ($q) use ($request) {
                $q->where('vendor_name', 'LIKE', '%'.$request->provider_search.'%')
                    ->orWhereHas('provider', function ($providerQuery) use ($request) {
                        $providerQuery->where('name', 'LIKE', '%'.$request->provider_search.'%');
                    });
            });
        }

        $expenses = $query->get();

        // Group by retention account (2365xx subaccounts)
        $retentionsByAccount = $expenses->groupBy('tax_account_id')
            ->map(function ($group) {
                $account = $group->first()->taxAccount;

                return [
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'count' => $group->count(),
                    'total_retained' => $group->sum('tax_amount'),
                    'expenses' => $group->map(function ($expense) {
                        return [
                            'id' => $expense->id,
                            'expense_number' => $expense->expense_number,
                            'vendor_name' => $expense->vendor_name ?? $expense->provider?->name,
                            'expense_date' => $expense->expense_date->format('Y-m-d'),
                            'subtotal' => $expense->subtotal,
                            'tax_amount' => $expense->tax_amount,
                            'category' => $expense->expenseCategory?->name,
                            'provider_id' => $expense->provider_id,
                        ];
                    })->values(),
                ];
            })
            ->values();

        // Total general (account 2365 total)
        $totalRetentions = $expenses->sum('tax_amount');

        // Get main account 2365
        $mainAccount = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', '2365')
            ->first();

        // Get all retention accounts for filter dropdown
        $retentionAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'LIKE', '2365%')
            ->where('accepts_posting', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        // Get providers with retentions for filter dropdown
        $providers = Provider::whereHas('expenses', function ($query) use ($conjunto) {
            $query->where('conjunto_config_id', $conjunto->id)
                ->where('tax_amount', '>', 0)
                ->whereNotNull('tax_account_id');
        })->get(['id', 'name']);

        return Inertia::render('Reports/WithholdingTaxReport', [
            'retentionsByAccount' => $retentionsByAccount,
            'totalRetentions' => $totalRetentions,
            'mainAccount' => $mainAccount,
            'retentionAccounts' => $retentionAccounts,
            'providers' => $providers,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'retention_type' => $request->input('retention_type'),
                'provider_id' => $request->input('provider_id'),
                'provider_search' => $request->input('provider_search'),
            ],
            'summary' => [
                'total_expenses_with_retention' => $expenses->count(),
                'total_providers' => $expenses->pluck('provider_id')->unique()->filter()->count(),
                'average_retention_rate' => $expenses->isEmpty() ? 0 : $expenses->avg(function ($expense) {
                    return ($expense->tax_amount / $expense->subtotal) * 100;
                }),
            ],
        ]);
    }

    public function generateCertificate(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'year' => 'required|integer|min:2020|max:'.now()->year,
        ]);

        $provider = Provider::findOrFail($request->provider_id);

        // Check if certificate already exists
        $existingCertificate = WithholdingCertificate::where('provider_id', $provider->id)
            ->where('year', $request->year)
            ->first();

        if ($existingCertificate) {
            return redirect()->back()->with('error', 'Ya existe un certificado para este proveedor en el año '.$request->year);
        }

        $certificate = $this->certificateService->generateForProvider($provider, $request->year);

        if (! $certificate) {
            return redirect()->back()->with('error', 'No se encontraron retenciones para este proveedor en el año '.$request->year);
        }

        return redirect()->back()->with('success', 'Certificado generado exitosamente: '.$certificate->certificate_number);
    }

    public function downloadCertificate(WithholdingCertificate $certificate)
    {
        return $this->certificateService->downloadPDF($certificate);
    }

    public function certificates(Request $request)
    {
        $conjunto = ConjuntoConfig::first();

        $query = WithholdingCertificate::forConjunto($conjunto->id)
            ->with(['provider', 'issuedBy']);

        if ($request->filled('year')) {
            $query->byYear($request->year);
        }

        if ($request->filled('provider_id')) {
            $query->byProvider($request->provider_id);
        }

        $certificates = $query->orderBy('issued_at', 'desc')->paginate(15);

        $providers = Provider::whereHas('withholdingCertificates')->get(['id', 'name']);

        return Inertia::render('Reports/WithholdingCertificates', [
            'certificates' => $certificates,
            'providers' => $providers,
            'filters' => [
                'year' => $request->input('year'),
                'provider_id' => $request->input('provider_id'),
            ],
        ]);
    }
}
