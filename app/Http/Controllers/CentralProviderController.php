<?php

namespace App\Http\Controllers;

use App\Models\Central\Provider;
use App\Models\Central\ProviderCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CentralProviderController extends Controller
{
    /**
     * Display a listing of central providers.
     */
    public function index(Request $request)
    {
        $query = Provider::query()->with(['categories']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } else {
                $query->where('is_active', false);
            }
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('provider_categories.id', $request->category_id);
            });
        }

        $providers = $query->orderBy('name')->paginate(15)->withQueryString();

        // Get counts by status
        $activeCount = Provider::where('is_active', true)->count();
        $inactiveCount = Provider::where('is_active', false)->count();

        // Get all categories for filter
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('admin/Providers/Index', [
            'providers' => $providers,
            'categories' => $categories,
            'filters' => $request->only(['status', 'search', 'category_id']),
            'stats' => [
                'active' => $activeCount,
                'inactive' => $inactiveCount,
                'total' => $activeCount + $inactiveCount,
            ],
        ]);
    }

    /**
     * Display the specified provider.
     */
    public function show(Provider $provider)
    {
        $provider->load(['categories']);

        return Inertia::render('admin/Providers/Show', [
            'provider' => $provider,
        ]);
    }

    /**
     * Show the form for editing the specified provider.
     */
    public function edit(Provider $provider)
    {
        $provider->load('categories');
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('admin/Providers/Edit', [
            'provider' => $provider,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified provider.
     */
    public function update(Request $request, Provider $provider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'document_type' => 'nullable|string|in:NIT,CC,CE,TI,PA,RUT',
            'document_number' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($provider) {
                    if ($value) {
                        $exists = Provider::where('document_number', $value)
                            ->where('id', '!=', $provider->id)
                            ->exists();

                        if ($exists) {
                            $fail('Ya existe un proveedor con este número de documento.');
                        }
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
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:provider_categories,id',
        ]);

        try {
            $provider->update([
                'name' => $validated['name'],
                'document_type' => $validated['document_type'] ?? null,
                'document_number' => $validated['document_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? 'Colombia',
                'contact_name' => $validated['contact_name'] ?? null,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'contact_email' => $validated['contact_email'] ?? null,
                'tax_regime' => $validated['tax_regime'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Sync categories (empty array will detach all categories)
            $provider->categories()->sync($validated['category_ids'] ?? []);

            // Refresh the provider to ensure relationships are loaded
            $provider->refresh();

            Log::info('Central provider updated', [
                'provider_id' => $provider->id,
                'updated_by' => auth()->id(),
                'categories_count' => $provider->categories()->count(),
            ]);

            return redirect()->route('admin.providers.show', $provider)
                ->with('success', 'Proveedor actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Central provider update failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al actualizar el proveedor.');
        }
    }

    /**
     * Toggle provider active status.
     */
    public function toggleStatus(Provider $provider)
    {
        try {
            $provider->update([
                'is_active' => !$provider->is_active,
            ]);

            $status = $provider->is_active ? 'activado' : 'desactivado';

            Log::info('Provider status toggled', [
                'provider_id' => $provider->id,
                'new_status' => $provider->is_active,
                'toggled_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', "Proveedor {$status} exitosamente.");
        } catch (\Exception $e) {
            Log::error('Provider status toggle failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Ocurrió un error al cambiar el estado del proveedor.');
        }
    }

    /**
     * Remove the specified provider.
     */
    public function destroy(Provider $provider)
    {
        try {
            // Soft delete the provider
            $provider->delete();

            Log::info('Provider deleted', [
                'provider_id' => $provider->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.providers.index')
                ->with('success', 'Proveedor eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Provider deletion failed', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Ocurrió un error al eliminar el proveedor.');
        }
    }
}
