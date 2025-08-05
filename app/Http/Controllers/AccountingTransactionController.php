<?php

namespace App\Http\Controllers;

use App\Models\AccountingTransaction;
use App\Models\AccountingTransactionEntry;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountingTransactionController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = AccountingTransaction::forConjunto($conjunto->id)
            ->with(['entries.account', 'createdBy', 'postedBy', 'reference']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byPeriod($request->start_date, $request->end_date);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(100)
            ->withQueryString();

        return Inertia::render('Accounting/Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['status', 'start_date', 'end_date', 'reference_type', 'search']),
            'statuses' => [
                'draft' => 'Borrador',
                'posted' => 'Contabilizado',
                'cancelled' => 'Cancelado',
            ],
            'referenceTypes' => [
                'invoice' => 'Factura',
                'payment' => 'Pago',
                'manual' => 'Manual',
            ],
        ]);
    }

    public function create(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $accounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        return Inertia::render('Accounting/Transactions/Create', [
            'accounts' => $accounts,
            'referenceTypes' => [
                'manual' => 'Manual',
                'adjustment' => 'Ajuste',
                'opening' => 'Apertura',
                'closing' => 'Cierre',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:500',
            'reference_type' => 'nullable|string|max:50',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,id',
            'entries.*.description' => 'required|string|max:255',
            'entries.*.debit_amount' => 'required|numeric|min:0',
            'entries.*.credit_amount' => 'required|numeric|min:0',
            'entries.*.third_party_type' => 'nullable|string|max:50',
            'entries.*.third_party_id' => 'nullable|integer',
        ]);

        // Validate double entry
        $totalDebits = collect($validated['entries'])->sum('debit_amount');
        $totalCredits = collect($validated['entries'])->sum('credit_amount');

        if ($totalDebits !== $totalCredits) {
            return back()
                ->withInput()
                ->withErrors(['entries' => 'Los débitos y créditos deben ser iguales (partida doble).']);
        }

        if ($totalDebits == 0) {
            return back()
                ->withInput()
                ->withErrors(['entries' => 'Debe haber al menos un movimiento con valor mayor a cero.']);
        }

        DB::transaction(function () use ($validated, $conjunto) {
            $transaction = AccountingTransaction::create([
                'conjunto_config_id' => $conjunto->id,
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'reference_type' => $validated['reference_type'] ?? 'manual',
                'status' => 'borrador',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['entries'] as $entryData) {
                $transaction->addEntry($entryData);
            }
        });

        return redirect()
            ->route('accounting-transactions.index')
            ->with('success', 'Transacción contable creada exitosamente.');
    }

    public function show(AccountingTransaction $transaction)
    {
        $transaction->load([
            'entries.account',
            'createdBy',
            'postedBy',
            'reference',
        ]);

        return Inertia::render('Accounting/Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function edit(AccountingTransaction $transaction)
    {
        if ($transaction->status !== 'draft') {
            return back()->withErrors(['transaction' => 'Solo se pueden editar transacciones en borrador.']);
        }

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $accounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $transaction->load('entries.account');

        return Inertia::render('Accounting/Transactions/Edit', [
            'transaction' => $transaction,
            'accounts' => $accounts,
            'referenceTypes' => [
                'manual' => 'Manual',
                'adjustment' => 'Ajuste',
                'opening' => 'Apertura',
                'closing' => 'Cierre',
            ],
        ]);
    }

    public function update(Request $request, AccountingTransaction $transaction)
    {
        if ($transaction->status !== 'draft') {
            return back()->withErrors(['transaction' => 'Solo se pueden editar transacciones en borrador.']);
        }

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:500',
            'reference_type' => 'nullable|string|max:50',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:chart_of_accounts,id',
            'entries.*.description' => 'required|string|max:255',
            'entries.*.debit_amount' => 'required|numeric|min:0',
            'entries.*.credit_amount' => 'required|numeric|min:0',
            'entries.*.third_party_type' => 'nullable|string|max:50',
            'entries.*.third_party_id' => 'nullable|integer',
        ]);

        // Validate double entry
        $totalDebits = collect($validated['entries'])->sum('debit_amount');
        $totalCredits = collect($validated['entries'])->sum('credit_amount');

        if ($totalDebits !== $totalCredits) {
            return back()
                ->withInput()
                ->withErrors(['entries' => 'Los débitos y créditos deben ser iguales (partida doble).']);
        }

        if ($totalDebits == 0) {
            return back()
                ->withInput()
                ->withErrors(['entries' => 'Debe haber al menos un movimiento con valor mayor a cero.']);
        }

        DB::transaction(function () use ($validated, $transaction) {
            // Update transaction
            $transaction->update([
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'reference_type' => $validated['reference_type'] ?? 'manual',
            ]);

            // Delete existing entries
            $transaction->entries()->delete();

            // Create new entries
            foreach ($validated['entries'] as $entryData) {
                $transaction->addEntry($entryData);
            }
        });

        return redirect()
            ->route('accounting-transactions.show', $transaction)
            ->with('success', 'Transacción contable actualizada exitosamente.');
    }

    public function destroy(AccountingTransaction $transaction)
    {
        if ($transaction->status === 'posted') {
            return back()->withErrors(['transaction' => 'No se pueden eliminar transacciones contabilizadas.']);
        }

        $transaction->entries()->delete();
        $transaction->delete();

        return redirect()
            ->route('accounting-transactions.index')
            ->with('success', 'Transacción contable eliminada exitosamente.');
    }

    public function post(AccountingTransaction $transaction)
    {
        try {
            $transaction->post();

            return back()->with('success', 'Transacción contabilizada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['transaction' => $e->getMessage()]);
        }
    }

    public function cancel(AccountingTransaction $transaction)
    {
        try {
            $transaction->cancel();

            return back()->with('success', 'Transacción cancelada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['transaction' => $e->getMessage()]);
        }
    }

    public function addEntry(Request $request, AccountingTransaction $transaction)
    {
        if ($transaction->status !== 'draft') {
            return response()->json(['error' => 'Solo se pueden agregar movimientos a transacciones en borrador.'], 422);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'description' => 'required|string|max:255',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
            'third_party_type' => 'nullable|string|max:50',
            'third_party_id' => 'nullable|integer',
        ]);

        try {
            $entry = $transaction->addEntry($validated);
            $entry->load('account');

            return response()->json([
                'entry' => $entry,
                'transaction' => $transaction->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function removeEntry(AccountingTransaction $transaction, AccountingTransactionEntry $entry)
    {
        if ($transaction->status !== 'draft') {
            return response()->json(['error' => 'Solo se pueden eliminar movimientos de transacciones en borrador.'], 422);
        }

        if ($entry->accounting_transaction_id !== $transaction->id) {
            return response()->json(['error' => 'El movimiento no pertenece a esta transacción.'], 422);
        }

        $entry->delete();
        $transaction->calculateTotals();

        return response()->json([
            'transaction' => $transaction->fresh(),
        ]);
    }

    public function validateDoubleEntry(Request $request)
    {
        $entries = $request->validate([
            'entries' => 'required|array|min:2',
            'entries.*.debit_amount' => 'required|numeric|min:0',
            'entries.*.credit_amount' => 'required|numeric|min:0',
        ])['entries'];

        $totalDebits = collect($entries)->sum('debit_amount');
        $totalCredits = collect($entries)->sum('credit_amount');

        return response()->json([
            'is_balanced' => $totalDebits === $totalCredits,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'difference' => abs($totalDebits - $totalCredits),
        ]);
    }
}
