<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriodClosure;
use App\Models\ConjuntoConfig;
use App\Services\AccountingClosureService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingClosureController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $service = new AccountingClosureService($conjunto->id);
        $closures = $service->getClosureHistory();

        return Inertia::render('Accounting/Closures/Index', [
            'closures' => $closures,
            'conjunto' => $conjunto,
        ]);
    }

    public function create(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        // Get available fiscal years (last 5 years + current year)
        $currentYear = now()->year;
        $availableYears = range($currentYear, $currentYear - 5);

        // Get already closed years
        $closedYears = AccountingPeriodClosure::forConjunto($conjunto->id)
            ->where('period_type', 'annual')
            ->completed()
            ->pluck('fiscal_year')
            ->toArray();

        return Inertia::render('Accounting/Closures/Create', [
            'conjunto' => $conjunto,
            'availableYears' => $availableYears,
            'closedYears' => $closedYears,
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $service = new AccountingClosureService($conjunto->id);

        try {
            $preview = $service->previewClosure($validated['fiscal_year']);

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'closure_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $service = new AccountingClosureService($conjunto->id);

        try {
            $result = $service->executeAnnualClosure(
                $validated['fiscal_year'],
                $validated['closure_date'],
                [
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            return redirect()
                ->route('accounting.closures.index')
                ->with('success', "Cierre contable del año {$validated['fiscal_year']} ejecutado exitosamente. Resultado neto: ".($result['is_profit'] ? 'Excedente' : 'Déficit').' de $'.number_format(abs($result['net_result']), 2));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error ejecutando cierre: '.$e->getMessage()]);
        }
    }

    public function show(AccountingPeriodClosure $closure)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if ($closure->conjunto_config_id !== $conjunto->id) {
            abort(403, 'No tiene permisos para ver este cierre.');
        }

        $closure->load(['closedByUser', 'closingTransaction.entries.account']);

        return Inertia::render('Accounting/Closures/Show', [
            'closure' => $closure,
            'conjunto' => $conjunto,
        ]);
    }

    public function reverse(AccountingPeriodClosure $closure, Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if ($closure->conjunto_config_id !== $conjunto->id) {
            abort(403, 'No tiene permisos para reversar este cierre.');
        }

        if (! $closure->canBeReversed()) {
            return back()->withErrors(['error' => 'Este cierre no puede ser reversado.']);
        }

        $service = new AccountingClosureService($conjunto->id);

        try {
            $result = $service->reverseClosure($closure->id);

            return redirect()
                ->route('accounting.closures.index')
                ->with('success', "Cierre del año {$closure->fiscal_year} reversado exitosamente. Se cancelaron {$result['transactions_cancelled']} transacciones.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error reversando cierre: '.$e->getMessage()]);
        }
    }
}
