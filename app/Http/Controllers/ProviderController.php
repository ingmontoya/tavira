<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = Provider::with(['createdBy']);

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

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Filter by type (global/local)
        if ($request->filled('type')) {
            if ($request->type === 'global') {
                $query->whereNotNull('global_provider_id');
            } elseif ($request->type === 'local') {
                $query->whereNull('global_provider_id');
            }
        }

        // Add expense counts before pagination
        $query->withCount([
            'expenses as expenses_count',
            'expenses as active_expenses' => function ($query) {
                $query->whereNotIn('status', ['cancelado']);
            },
        ]);

        $providers = $query->orderBy('name')->paginate(25);

        // Load last expense date for each provider
        $providers->each(function ($provider) {
            $lastExpense = $provider->expenses()->orderBy('expense_date', 'desc')->first();
            $provider->last_expense_date = $lastExpense ? $lastExpense->expense_date : null;
        });

        // Summary stats
        $stats = [
            'total_providers' => Provider::count(),
            'active_providers' => Provider::active()->count(),
            'inactive_providers' => Provider::where('is_active', false)->count(),
            'global_providers' => Provider::whereNotNull('global_provider_id')->count(),
            'local_providers' => Provider::whereNull('global_provider_id')->count(),
            'total_expenses' => Expense::whereHas('provider')->sum('total_amount'),
        ];

        return Inertia::render('Providers/Index', [
            'providers' => $providers,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'document_type', 'category', 'type']),
        ]);
    }

    public function show(Request $request, Provider $provider)
    {
        $provider->load(['createdBy']);

        // Load expenses count and total
        $provider->loadCount(['expenses as expenses_count']);
        $provider->total_expenses = $provider->expenses()->sum('total_amount');

        // Load paginated expenses
        $expenses = $provider->expenses()
            ->with(['expenseCategory', 'debitAccount', 'creditAccount'])
            ->orderBy('expense_date', 'desc')
            ->paginate(10);

        // Calculate stats
        $stats = [
            'total_expenses' => $provider->expenses()->count(),
            'total_amount' => $provider->expenses()->sum('total_amount'),
            'pending_amount' => $provider->expenses()->whereIn('status', ['borrador', 'pendiente'])->sum('total_amount'),
            'pending_expenses' => $provider->expenses()->whereIn('status', ['borrador', 'pendiente'])->count(),
            'approved_expenses' => $provider->expenses()->where('status', 'aprobado')->count(),
            'paid_amount' => $provider->expenses()->where('status', 'pagado')->sum('total_amount'),
            'paid_expenses' => $provider->expenses()->where('status', 'pagado')->count(),
            'overdue_expenses' => $provider->expenses()->where('status', '!=', 'pagado')
                ->where('due_date', '<', now())->count(),
            'average_amount' => $provider->expenses()->count() > 0
                ? $provider->expenses()->avg('total_amount')
                : 0,
        ];

        return Inertia::render('Providers/Show', [
            'provider' => $provider,
            'expenses' => $expenses,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('Providers/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'required|string|in:NIT,CC,CE,TI,PA',
            'document_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $exists = Provider::where('document_number', $value)->exists();

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
            'category' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'tax_regime' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['country'] = $validated['country'] ?? 'Colombia';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $provider = Provider::create($validated);

        return redirect()->route('providers.show', $provider)
            ->with('success', 'Proveedor creado exitosamente');
    }

    public function edit(Provider $provider)
    {
        // Check if provider is synced (global)
        if ($provider->isSynced()) {
            return redirect()->route('providers.show', $provider)
                ->with('error', 'No se pueden editar proveedores globales. Contacte al administrador central.');
        }

        return Inertia::render('Providers/Edit', [
            'provider' => $provider,
        ]);
    }

    public function update(Request $request, Provider $provider)
    {
        // Check if provider is synced (global)
        if ($provider->isSynced()) {
            return redirect()->route('providers.show', $provider)
                ->with('error', 'No se pueden editar proveedores globales. Contacte al administrador central.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'required|string|in:NIT,CC,CE,TI,PA,RUT',
            'document_number' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($provider) {
                    $exists = Provider::where('document_number', $value)
                        ->where('id', '!=', $provider->id)
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
            'category' => 'nullable|string|max:100',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'tax_regime' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $provider->update($validated);

        return redirect()->route('providers.show', $provider)
            ->with('success', 'Proveedor actualizado exitosamente');
    }

    public function destroy(Provider $provider)
    {
        // Check if provider is synced (global)
        if ($provider->isSynced()) {
            return redirect()->route('providers.index')
                ->with('error', 'No se pueden eliminar proveedores globales.');
        }

        if (! $provider->canBeDeleted()) {
            return redirect()->route('providers.index')
                ->with('error', 'No se puede eliminar el proveedor porque tiene gastos o solicitudes de mantenimiento asociadas');
        }

        $provider->delete();

        return redirect()->route('providers.index')
            ->with('success', 'Proveedor eliminado exitosamente');
    }

    public function toggleStatus(Provider $provider)
    {
        // Check if provider is synced (global)
        if ($provider->isSynced()) {
            return redirect()->route('providers.show', $provider)
                ->with('error', 'No se puede cambiar el estado de proveedores globales.');
        }

        if ($provider->is_active) {
            $provider->deactivate();
            $message = 'Proveedor desactivado exitosamente';
        } else {
            $provider->activate();
            $message = 'Proveedor activado exitosamente';
        }

        return redirect()->route('providers.show', $provider)
            ->with('success', $message);
    }
}
