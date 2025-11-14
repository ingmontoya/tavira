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
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfYear()->format('Y-m-d'));

        // Build query for expenses with retention
        $query = Expense::forConjunto($conjunto->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->whereNotNull('provider_id')
            ->with(['provider', 'taxAccount', 'expenseCategory']);

        // Filter by provider name (search)
        if ($request->filled('provider_search')) {
            $query->whereHas('provider', function ($providerQuery) use ($request) {
                $providerQuery->where('name', 'LIKE', '%'.$request->provider_search.'%');
            });
        }

        $expenses = $query->get();

        // Group by provider
        $providersSummary = $expenses->groupBy('provider_id')
            ->map(function ($group) {
                $provider = $group->first()->provider;

                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'document_type' => $provider->document_type,
                    'document_number' => $provider->document_number,
                    'expenses_count' => $group->count(),
                    'total_retained' => $group->sum('tax_amount'),
                ];
            })
            ->sortByDesc('total_retained')
            ->values();

        // Total general
        $totalRetentions = $expenses->sum('tax_amount');

        return Inertia::render('Reports/WithholdingTaxReport', [
            'providers' => $providersSummary,
            'totalRetentions' => $totalRetentions,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'provider_search' => $request->input('provider_search'),
            ],
            'summary' => [
                'total_expenses_with_retention' => $expenses->count(),
                'total_providers' => $providersSummary->count(),
                'average_retention_rate' => $expenses->isEmpty() ? 0 : $expenses->avg(function ($expense) {
                    return ($expense->tax_amount / $expense->subtotal) * 100;
                }),
            ],
        ]);
    }

    public function showProvider(Request $request, Provider $provider)
    {
        $conjunto = ConjuntoConfig::first();

        // Filtros de fecha
        $startDate = $request->input('start_date', now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfYear()->format('Y-m-d'));

        // Get expenses with retention for this provider
        $expenses = Expense::forConjunto($conjunto->id)
            ->where('provider_id', $provider->id)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->with(['taxAccount', 'expenseCategory'])
            ->orderBy('expense_date', 'desc')
            ->get();

        // Group by retention type (tax account)
        $retentionsByType = $expenses->groupBy('tax_account_id')
            ->map(function ($group) {
                $account = $group->first()->taxAccount;

                return [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'count' => $group->count(),
                    'total_retained' => $group->sum('tax_amount'),
                ];
            })
            ->values();

        // Total retained
        $totalRetained = $expenses->sum('tax_amount');

        // Format expenses for display
        $expensesFormatted = $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'expense_date' => $expense->expense_date->format('Y-m-d'),
                'subtotal' => $expense->subtotal,
                'tax_amount' => $expense->tax_amount,
                'tax_rate' => $expense->tax_rate,
                'category' => $expense->expenseCategory?->name,
                'tax_account_code' => $expense->taxAccount->code,
                'tax_account_name' => $expense->taxAccount->name,
            ];
        });

        // Get existing certificates for this provider
        $certificates = WithholdingCertificate::where('provider_id', $provider->id)
            ->orderBy('year', 'desc')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'certificate_number' => $cert->certificate_number,
                    'year' => $cert->year,
                    'issued_at' => $cert->issued_at->toISOString(),
                    'total_withheld' => $cert->total_retained,
                ];
            });

        return Inertia::render('Reports/WithholdingProviderDetail', [
            'provider' => [
                'id' => $provider->id,
                'name' => $provider->name,
                'document_type' => $provider->document_type,
                'document_number' => $provider->document_number,
                'address' => $provider->address,
                'email' => $provider->email,
            ],
            'expenses' => $expensesFormatted,
            'retentionsByType' => $retentionsByType,
            'totalRetained' => $totalRetained,
            'certificates' => $certificates,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
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
