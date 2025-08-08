<?php

namespace App\Http\Controllers;

use App\Models\ConjuntoConfig;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = Supplier::with(['createdBy']);

        // Filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        $suppliers = $query->forConjunto($conjunto->id)
            ->orderBy('name')
            ->paginate(25);

        // Load expense counts and last expense date for each supplier
        $suppliers->each(function ($supplier) {
            $supplier->loadCount(['expenses as expenses_count', 'expenses as active_expenses' => function ($query) {
                $query->whereNotIn('status', ['cancelado']);
            }]);

            // Load last expense date
            $lastExpense = $supplier->expenses()->orderBy('expense_date', 'desc')->first();
            $supplier->last_expense_date = $lastExpense ? $lastExpense->expense_date : null;
        });

        // Summary stats
        $stats = [
            'total_suppliers' => Supplier::forConjunto($conjunto->id)->count(),
            'active_suppliers' => Supplier::forConjunto($conjunto->id)->active()->count(),
            'inactive_suppliers' => Supplier::forConjunto($conjunto->id)->where('is_active', false)->count(),
        ];

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'document_type']),
        ]);
    }

    public function show(Supplier $supplier)
    {
        $supplier->load([
            'createdBy',
            'expenses' => function ($query) {
                $query->with(['expenseCategory', 'debitAccount', 'creditAccount'])
                    ->orderBy('expense_date', 'desc')
                    ->limit(10);
            },
        ]);

        $supplier->loadCount([
            'expenses as total_expenses',
            'expenses as total_amount' => function ($query) {
                $query->selectRaw('SUM(total_amount) as total');
            },
        ]);

        // Recent expenses summary
        $expenseStats = [
            'total_expenses' => $supplier->expenses()->count(),
            'total_amount' => $supplier->expenses()->sum('total_amount'),
            'pending_amount' => $supplier->expenses()->whereIn('status', ['borrador', 'pendiente', 'aprobado'])->sum('total_amount'),
            'paid_amount' => $supplier->expenses()->where('status', 'pagado')->sum('total_amount'),
        ];

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
            'expenseStats' => $expenseStats,
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'required|string|in:NIT,CC,CE,TI,PA',
            'document_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($conjunto) {
                    $exists = Supplier::forConjunto($conjunto->id)
                        ->where('document_number', $value)
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un proveedor con este número de documento.');
                    }
                },
            ],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'tax_regime' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['conjunto_config_id'] = $conjunto->id;
        $validated['created_by'] = auth()->id();
        $validated['country'] = $validated['country'] ?? 'Colombia';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $supplier = Supplier::create($validated);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Proveedor creado exitosamente');
    }

    public function edit(Supplier $supplier)
    {
        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'required|string|in:NIT,CC,CE,TI,PA',
            'document_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($supplier) {
                    $exists = Supplier::forConjunto($supplier->conjunto_config_id)
                        ->where('document_number', $value)
                        ->where('id', '!=', $supplier->id)
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un proveedor con este número de documento.');
                    }
                },
            ],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'tax_regime' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', 'Proveedor actualizado exitosamente');
    }

    public function destroy(Supplier $supplier)
    {
        if (! $supplier->canBeDeleted()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'No se puede eliminar el proveedor porque tiene gastos asociados');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor eliminado exitosamente');
    }

    public function toggleStatus(Supplier $supplier)
    {
        if ($supplier->is_active) {
            $supplier->deactivate();
            $message = 'Proveedor desactivado exitosamente';
        } else {
            $supplier->activate();
            $message = 'Proveedor activado exitosamente';
        }

        return redirect()->route('suppliers.show', $supplier)
            ->with('success', $message);
    }
}
