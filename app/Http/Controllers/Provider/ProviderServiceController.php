<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Central\Provider;
use App\Models\Central\ProviderCategory;
use App\Models\Central\ProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProviderServiceController extends Controller
{
    /**
     * Get the provider for the authenticated user.
     */
    private function getProvider(Request $request): Provider
    {
        $provider = Provider::find($request->user()->provider_id);

        if (!$provider) {
            abort(403, 'No tienes un perfil de proveedor asociado.');
        }

        return $provider;
    }

    /**
     * Display a listing of the services.
     */
    public function index(Request $request)
    {
        $provider = $this->getProvider($request);

        $query = ProviderService::query()
            ->with('category')
            ->byProvider($provider->id);

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $services = $query->latest()->paginate(15)->withQueryString();

        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('Provider/Services/Index', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['status', 'category', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(Request $request)
    {
        $this->getProvider($request);
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('Provider/Services/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request)
    {
        $provider = $this->getProvider($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'price_type' => 'required|in:fixed,hourly,per_unit,quote',
            'unit' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:provider_categories,id',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:5',
            'images.*' => 'url',
            'specifications' => 'nullable|array',
            'terms' => 'nullable|string|max:2000',
            'estimated_delivery_days' => 'nullable|integer|min:0',
        ]);

        try {
            $service = ProviderService::create([
                'provider_id' => $provider->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'] ?? null,
                'price_type' => $validated['price_type'],
                'unit' => $validated['unit'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'images' => $validated['images'] ?? null,
                'specifications' => $validated['specifications'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'estimated_delivery_days' => $validated['estimated_delivery_days'] ?? null,
            ]);

            Log::info('Provider service created', [
                'service_id' => $service->id,
                'provider_id' => $provider->id,
            ]);

            return redirect()->route('provider.services.index')
                ->with('success', 'Servicio creado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Provider service creation failed', [
                'error' => $e->getMessage(),
                'provider_id' => $provider->id,
            ]);

            return back()->withInput()->with('error', 'Ocurrió un error al crear el servicio.');
        }
    }

    /**
     * Display the specified service.
     */
    public function show(Request $request, ProviderService $service)
    {
        $provider = $this->getProvider($request);

        // Ensure the service belongs to the provider
        if ($service->provider_id !== $provider->id) {
            abort(403, 'No tienes permiso para ver este servicio.');
        }

        $service->load('category');

        return Inertia::render('Provider/Services/Show', [
            'service' => $service,
        ]);
    }

    /**
     * Show the form for editing the specified service.
     */
    public function edit(Request $request, ProviderService $service)
    {
        $provider = $this->getProvider($request);

        // Ensure the service belongs to the provider
        if ($service->provider_id !== $provider->id) {
            abort(403, 'No tienes permiso para editar este servicio.');
        }

        $service->load('category');
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('Provider/Services/Edit', [
            'service' => $service,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, ProviderService $service)
    {
        $provider = $this->getProvider($request);

        // Ensure the service belongs to the provider
        if ($service->provider_id !== $provider->id) {
            abort(403, 'No tienes permiso para actualizar este servicio.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'price_type' => 'required|in:fixed,hourly,per_unit,quote',
            'unit' => 'nullable|string|max:50',
            'category_id' => 'nullable|exists:provider_categories,id',
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:5',
            'images.*' => 'url',
            'specifications' => 'nullable|array',
            'terms' => 'nullable|string|max:2000',
            'estimated_delivery_days' => 'nullable|integer|min:0',
        ]);

        try {
            $service->update($validated);

            Log::info('Provider service updated', [
                'service_id' => $service->id,
                'provider_id' => $provider->id,
            ]);

            return redirect()->route('provider.services.index')
                ->with('success', 'Servicio actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Provider service update failed', [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
            ]);

            return back()->withInput()->with('error', 'Ocurrió un error al actualizar el servicio.');
        }
    }

    /**
     * Remove the specified service.
     */
    public function destroy(Request $request, ProviderService $service)
    {
        $provider = $this->getProvider($request);

        // Ensure the service belongs to the provider
        if ($service->provider_id !== $provider->id) {
            abort(403, 'No tienes permiso para eliminar este servicio.');
        }

        try {
            $service->delete();

            Log::info('Provider service deleted', [
                'service_id' => $service->id,
                'provider_id' => $provider->id,
            ]);

            return redirect()->route('provider.services.index')
                ->with('success', 'Servicio eliminado exitosamente.');
        } catch (\Exception $e) {
            Log::error('Provider service deletion failed', [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
            ]);

            return back()->with('error', 'Ocurrió un error al eliminar el servicio.');
        }
    }

    /**
     * Toggle service active status.
     */
    public function toggleStatus(Request $request, ProviderService $service)
    {
        $provider = $this->getProvider($request);

        // Ensure the service belongs to the provider
        if ($service->provider_id !== $provider->id) {
            abort(403, 'No tienes permiso para cambiar el estado de este servicio.');
        }

        try {
            $service->update(['is_active' => !$service->is_active]);

            $status = $service->is_active ? 'activado' : 'desactivado';

            Log::info('Provider service status toggled', [
                'service_id' => $service->id,
                'new_status' => $service->is_active,
            ]);

            return back()->with('success', "Servicio {$status} exitosamente.");
        } catch (\Exception $e) {
            Log::error('Provider service status toggle failed', [
                'error' => $e->getMessage(),
                'service_id' => $service->id,
            ]);

            return back()->with('error', 'Ocurrió un error al cambiar el estado del servicio.');
        }
    }
}
