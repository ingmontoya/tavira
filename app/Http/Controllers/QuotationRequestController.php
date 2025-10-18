<?php

namespace App\Http\Controllers;

use App\Mail\QuotationRequestNotification;
use App\Models\Central\Provider;
use App\Models\ProviderCategory;
use App\Models\QuotationRequest;
use App\Models\QuotationResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class QuotationRequestController extends Controller
{
    /**
     * Display a listing of quotation requests.
     */
    public function index(Request $request)
    {
        $query = QuotationRequest::query()->with(['createdBy', 'categories', 'responses']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $quotationRequests = $query->latest()->paginate(15)->withQueryString();

        // Get counts by status
        $stats = [
            'draft' => QuotationRequest::draft()->count(),
            'published' => QuotationRequest::published()->count(),
            'closed' => QuotationRequest::closed()->count(),
            'total' => QuotationRequest::count(),
        ];

        return Inertia::render('quotation-requests/Index', [
            'quotationRequests' => $quotationRequests,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new quotation request.
     */
    public function create()
    {
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('quotation-requests/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created quotation request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'deadline' => 'nullable|date|after:today',
            'requirements' => 'nullable|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:provider_categories,id',
            'publish_now' => 'boolean',
        ]);

        try {
            $quotationRequest = QuotationRequest::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'deadline' => $validated['deadline'] ?? null,
                'requirements' => $validated['requirements'] ?? null,
                'status' => $validated['publish_now'] ?? false ? 'published' : 'draft',
                'created_by' => auth()->id(),
                'published_at' => $validated['publish_now'] ?? false ? now() : null,
            ]);

            // Attach categories
            $quotationRequest->categories()->attach($validated['category_ids']);

            // If publishing now, send notifications to providers
            if ($validated['publish_now'] ?? false) {
                $this->notifyProviders($quotationRequest);
            }

            Log::info('Quotation request created', [
                'quotation_request_id' => $quotationRequest->id,
                'created_by' => auth()->id(),
                'status' => $quotationRequest->status,
            ]);

            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('success', 'Solicitud de cotización creada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation request creation failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al crear la solicitud.');
        }
    }

    /**
     * Display the specified quotation request.
     */
    public function show(QuotationRequest $quotationRequest)
    {
        $quotationRequest->load(['createdBy', 'categories', 'responses.provider']);

        return Inertia::render('quotation-requests/Show', [
            'quotationRequest' => $quotationRequest,
        ]);
    }

    /**
     * Show the form for editing the specified quotation request.
     */
    public function edit(QuotationRequest $quotationRequest)
    {
        // Only allow editing draft requests
        if ($quotationRequest->status !== 'draft') {
            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('error', 'Solo se pueden editar solicitudes en borrador.');
        }

        $quotationRequest->load('categories');
        $categories = ProviderCategory::active()->ordered()->get();

        return Inertia::render('quotation-requests/Edit', [
            'quotationRequest' => $quotationRequest,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified quotation request.
     */
    public function update(Request $request, QuotationRequest $quotationRequest)
    {
        // Only allow editing draft requests
        if ($quotationRequest->status !== 'draft') {
            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('error', 'Solo se pueden editar solicitudes en borrador.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'deadline' => 'nullable|date|after:today',
            'requirements' => 'nullable|string',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:provider_categories,id',
        ]);

        try {
            $quotationRequest->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'deadline' => $validated['deadline'] ?? null,
                'requirements' => $validated['requirements'] ?? null,
            ]);

            // Sync categories
            $quotationRequest->categories()->sync($validated['category_ids']);

            Log::info('Quotation request updated', [
                'quotation_request_id' => $quotationRequest->id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('success', 'Solicitud actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation request update failed', [
                'quotation_request_id' => $quotationRequest->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al actualizar la solicitud.');
        }
    }

    /**
     * Publish a quotation request.
     */
    public function publish(QuotationRequest $quotationRequest)
    {
        if ($quotationRequest->status !== 'draft') {
            return redirect()->back()->with('error', 'Esta solicitud ya fue publicada.');
        }

        try {
            $quotationRequest->publish();

            // Send notifications to providers in the selected categories
            $this->notifyProviders($quotationRequest);

            Log::info('Quotation request published', [
                'quotation_request_id' => $quotationRequest->id,
                'published_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Solicitud publicada y proveedores notificados.');
        } catch (\Exception $e) {
            Log::error('Quotation request publish failed', [
                'quotation_request_id' => $quotationRequest->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al publicar la solicitud.');
        }
    }

    /**
     * Close a quotation request.
     */
    public function close(QuotationRequest $quotationRequest)
    {
        if ($quotationRequest->status !== 'published') {
            return redirect()->back()->with('error', 'Solo se pueden cerrar solicitudes publicadas.');
        }

        try {
            $quotationRequest->close();

            Log::info('Quotation request closed', [
                'quotation_request_id' => $quotationRequest->id,
                'closed_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Solicitud cerrada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation request close failed', [
                'quotation_request_id' => $quotationRequest->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al cerrar la solicitud.');
        }
    }

    /**
     * Delete a quotation request.
     */
    public function destroy(QuotationRequest $quotationRequest)
    {
        // Only allow deleting draft requests
        if ($quotationRequest->status !== 'draft') {
            return redirect()->back()->with('error', 'Solo se pueden eliminar solicitudes en borrador.');
        }

        try {
            $quotationRequest->delete();

            Log::info('Quotation request deleted', [
                'quotation_request_id' => $quotationRequest->id,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('quotation-requests.index')
                ->with('success', 'Solicitud eliminada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation request deletion failed', [
                'quotation_request_id' => $quotationRequest->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al eliminar la solicitud.');
        }
    }

    /**
     * Approve a quotation response.
     */
    public function approveResponse(QuotationRequest $quotationRequest, QuotationResponse $response)
    {
        // Verify that the response belongs to this quotation request
        if ($response->quotation_request_id !== $quotationRequest->id) {
            return redirect()->back()->with('error', 'La cotización no pertenece a esta solicitud.');
        }

        // Check if response is already approved or rejected
        if ($response->isAccepted()) {
            return redirect()->back()->with('warning', 'Esta cotización ya ha sido aprobada.');
        }

        if ($response->isRejected()) {
            return redirect()->back()->with('error', 'No se puede aprobar una cotización rechazada.');
        }

        try {
            $response->accept();

            Log::info('Quotation response approved', [
                'quotation_request_id' => $quotationRequest->id,
                'quotation_response_id' => $response->id,
                'approved_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Cotización aprobada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation response approval failed', [
                'quotation_request_id' => $quotationRequest->id,
                'quotation_response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al aprobar la cotización.');
        }
    }

    /**
     * Reject a quotation response.
     */
    public function rejectResponse(Request $request, QuotationRequest $quotationRequest, QuotationResponse $response)
    {
        // Verify that the response belongs to this quotation request
        if ($response->quotation_request_id !== $quotationRequest->id) {
            return redirect()->back()->with('error', 'La cotización no pertenece a esta solicitud.');
        }

        // Check if response is already rejected or approved
        if ($response->isRejected()) {
            return redirect()->back()->with('warning', 'Esta cotización ya ha sido rechazada.');
        }

        if ($response->isAccepted()) {
            return redirect()->back()->with('error', 'No se puede rechazar una cotización aprobada.');
        }

        // Validate optional admin notes
        $validated = $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $response->reject($validated['admin_notes'] ?? null);

            Log::info('Quotation response rejected', [
                'quotation_request_id' => $quotationRequest->id,
                'quotation_response_id' => $response->id,
                'rejected_by' => auth()->id(),
            ]);

            return redirect()->back()->with('success', 'Cotización rechazada exitosamente.');
        } catch (\Exception $e) {
            Log::error('Quotation response rejection failed', [
                'quotation_request_id' => $quotationRequest->id,
                'quotation_response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al rechazar la cotización.');
        }
    }

    /**
     * Notify providers about a new quotation request.
     */
    protected function notifyProviders(QuotationRequest $quotationRequest): void
    {
        $quotationRequest->load('categories');

        // Get all active providers that have at least one of the selected categories
        $categoryIds = $quotationRequest->categories->pluck('id')->toArray();

        $providers = Provider::active()
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('provider_category_id', $categoryIds);
            })
            ->get();

        foreach ($providers as $provider) {
            Mail::to($provider->email)->queue(
                new QuotationRequestNotification($quotationRequest, $provider)
            );
        }

        Log::info('Quotation request notifications sent', [
            'quotation_request_id' => $quotationRequest->id,
            'providers_notified' => $providers->count(),
        ]);
    }
}
