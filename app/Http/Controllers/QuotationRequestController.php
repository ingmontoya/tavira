<?php

namespace App\Http\Controllers;

use App\Mail\QuotationRequestNotification;
use App\Mail\QuotationResponseApproved;
use App\Mail\QuotationResponseRejected;
use App\Models\Central\Provider;
use App\Models\ConjuntoConfig;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ProviderCategory;
use App\Models\QuotationRequest;
use App\Models\QuotationResponse;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Force fresh data from database
        $quotationRequest = QuotationRequest::with(['createdBy', 'categories', 'responses'])
            ->findOrFail($quotationRequest->id);

        // Manually attach provider data from central database
        // (cannot use eager loading due to cross-database relationship)
        $providerIds = $quotationRequest->responses->pluck('provider_id')->unique()->toArray();
        $providers = Provider::whereIn('id', $providerIds)->get()->keyBy('id');

        // Transform responses to ensure fresh data and attach provider
        $responsesData = $quotationRequest->responses->map(function ($response) use ($providers) {
            // Force fresh from database with expense relationship
            $freshResponse = QuotationResponse::with('expense')->find($response->id);

            return [
                'id' => $freshResponse->id,
                'quotation_request_id' => $freshResponse->quotation_request_id,
                'provider_id' => $freshResponse->provider_id,
                'quoted_amount' => $freshResponse->quoted_amount,
                'proposal' => $freshResponse->proposal,
                'estimated_days' => $freshResponse->estimated_days,
                'attachments' => $freshResponse->attachments,
                'status' => $freshResponse->status,
                'admin_notes' => $freshResponse->admin_notes,
                'expense_id' => $freshResponse->expense_id,
                'expense' => $freshResponse->expense,
                'created_at' => $freshResponse->created_at,
                'updated_at' => $freshResponse->updated_at,
                'provider' => $providers->get($freshResponse->provider_id),
            ];
        });

        // Replace the responses collection with the transformed data
        $quotationRequest->setRelation('responses', collect($responsesData));

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
     * Display a specific quotation response.
     */
    public function showResponse(QuotationRequest $quotationRequest, QuotationResponse $response)
    {
        // Verify that the response belongs to this quotation request
        if ($response->quotation_request_id !== $quotationRequest->id) {
            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('error', 'La cotización no pertenece a esta solicitud.');
        }

        $quotationRequest->load(['createdBy', 'categories']);
        $response->load('expense');

        // Get provider from central database
        $provider = Provider::find($response->provider_id);

        if (! $provider) {
            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('error', 'Proveedor no encontrado.');
        }

        // Attach provider to response
        $response->provider = $provider;

        return Inertia::render('quotation-requests/ResponseShow', [
            'quotationRequest' => $quotationRequest,
            'response' => $response,
            'provider' => $provider,
        ]);
    }

    /**
     * Download attachment from a quotation response.
     */
    public function downloadAttachment(QuotationRequest $quotationRequest, QuotationResponse $response)
    {
        // Verify that the response belongs to this quotation request
        if ($response->quotation_request_id !== $quotationRequest->id) {
            return redirect()->route('quotation-requests.show', $quotationRequest)
                ->with('error', 'La cotización no pertenece a esta solicitud.');
        }

        if (! $response->attachments) {
            return redirect()->back()->with('error', 'Esta cotización no tiene adjuntos.');
        }

        $filePath = storage_path('app/public/'.$response->attachments);

        if (! file_exists($filePath)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }

        return response()->download($filePath);
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

        // Check if expense already exists
        if ($response->expense_id) {
            return redirect()->back()->with('warning', 'Ya existe un gasto asociado a esta cotización.');
        }

        try {
            DB::transaction(function () use ($quotationRequest, $response, &$expense, &$otherResponses, &$provider) {
                // 1. Accept the response
                $response->accept();

                // 2. Create the expense automatically
                $expense = $this->createExpenseFromQuotation($quotationRequest, $response);

                // 3. Link the response with the expense
                $response->update(['expense_id' => $expense->id]);

                // 4. Close the quotation request since a proposal has been accepted
                $quotationRequest->close();

                // 5. Get the provider from central database
                $provider = Provider::find($response->provider_id);

                if ($provider) {
                    // Send approval email to the selected provider
                    Mail::to($provider->email)->queue(
                        new QuotationResponseApproved($quotationRequest, $response, $provider)
                    );

                    // Get all other pending responses for this quotation request
                    $otherResponses = $quotationRequest->responses()
                        ->where('id', '!=', $response->id)
                        ->where('status', 'pending')
                        ->get();

                    // Send notification to other providers that they were not selected
                    foreach ($otherResponses as $otherResponse) {
                        // Reject the other responses
                        $otherResponse->reject('Se seleccionó otra propuesta');

                        // Get provider and send email
                        $otherProvider = Provider::find($otherResponse->provider_id);
                        if ($otherProvider) {
                            Mail::to($otherProvider->email)->queue(
                                new QuotationResponseRejected($quotationRequest, $otherResponse, $otherProvider)
                            );
                        }
                    }
                }
            });

            Log::info('Quotation response approved and expense created', [
                'quotation_request_id' => $quotationRequest->id,
                'quotation_response_id' => $response->id,
                'expense_id' => $expense->id,
                'expense_status' => $expense->status,
                'approved_by' => auth()->id(),
                'notified_providers' => ($otherResponses ?? collect())->count() + 1,
                'request_status' => 'closed',
            ]);

            $message = 'Cotización aprobada y gasto creado exitosamente. ';
            $message .= $expense->status === 'pendiente_concejo'
                ? 'El gasto requiere aprobación del concejo.'
                : ($expense->status === 'aprobado'
                    ? 'El gasto está listo para pago.'
                    : 'El gasto está pendiente de aprobación.');

            return redirect()->route('expenses.show', $expense)
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Quotation response approval failed', [
                'quotation_request_id' => $quotationRequest->id,
                'quotation_response_id' => $response->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Ocurrió un error al aprobar la cotización: '.$e->getMessage());
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

    /**
     * Create an expense from an approved quotation response.
     */
    protected function createExpenseFromQuotation(
        QuotationRequest $quotationRequest,
        QuotationResponse $response
    ): Expense {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $centralProvider = Provider::find($response->provider_id);

        if (! $centralProvider) {
            throw new \Exception('Proveedor no encontrado en la base de datos central');
        }

        // Create or sync provider in tenant database
        $tenantProvider = $this->syncProviderToTenant($centralProvider);

        // Get the expense category for quotation-based expenses
        $expenseCategory = $this->getExpenseCategoryForQuotation($conjunto);

        if (! $expenseCategory) {
            throw new \Exception('Categoría de gasto no encontrada. Por favor ejecute el seeder QuotationExpenseCategorySeeder.');
        }

        // Calculate tax (assuming IVA 19% if applicable)
        // You can adjust this logic based on your needs
        $subtotal = $response->quoted_amount / 1.19; // Assuming amount includes tax
        $taxAmount = $response->quoted_amount - $subtotal;

        $expenseData = [
            'conjunto_config_id' => $conjunto->id,
            'expense_category_id' => $expenseCategory->id,
            'provider_id' => $tenantProvider->id,
            'vendor_name' => $centralProvider->name,
            'vendor_document' => $centralProvider->tax_id,
            'vendor_email' => $centralProvider->email,
            'vendor_phone' => $centralProvider->phone,
            'description' => "Solicitud de cotización #{$quotationRequest->id}: {$quotationRequest->title}",
            'expense_date' => now()->toDateString(),
            'due_date' => now()->addDays($response->estimated_days ?? 30)->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $response->quoted_amount,
            'debit_account_id' => $expenseCategory->default_debit_account_id,
            'credit_account_id' => $expenseCategory->default_credit_account_id,
            'notes' => "Generado automáticamente desde solicitud de cotización #{$quotationRequest->id}\n".
                       "Proveedor seleccionado: {$centralProvider->name}\n".
                       "Propuesta: {$response->proposal}",
            'created_by' => auth()->id(),
        ];

        // The ExpenseService handles:
        // - Determining status based on threshold and configuration
        // - Creating accounting transactions if applicable
        // - Sending notifications
        $expenseService = app(ExpenseService::class);

        return $expenseService->create($expenseData);
    }

    /**
     * Get or create the expense category for quotation-based expenses.
     */
    protected function getExpenseCategoryForQuotation(ConjuntoConfig $conjunto): ?ExpenseCategory
    {
        return ExpenseCategory::where('conjunto_config_id', $conjunto->id)
            ->where('name', 'Servicios Contratados')
            ->first();
    }

    /**
     * Sync provider from central database to tenant database.
     * Creates or updates the provider in the tenant database.
     */
    protected function syncProviderToTenant(Provider $centralProvider): \App\Models\Provider
    {
        // The tenant Provider model (different from central)
        $tenantProviderModel = \App\Models\Provider::class;

        // Find or create provider in tenant database using central provider ID as external reference
        $tenantProvider = $tenantProviderModel::updateOrCreate(
            ['email' => $centralProvider->email], // Match by email
            [
                'name' => $centralProvider->name,
                'tax_id' => $centralProvider->tax_id,
                'phone' => $centralProvider->phone,
                'address' => $centralProvider->address,
                'city' => $centralProvider->city,
                'contact_name' => $centralProvider->contact_name,
                'notes' => 'Sincronizado desde base de datos central (ID: '.$centralProvider->id.')',
                'is_active' => true,
            ]
        );

        return $tenantProvider;
    }
}
