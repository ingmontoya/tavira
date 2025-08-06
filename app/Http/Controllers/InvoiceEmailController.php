<?php

namespace App\Http\Controllers;

use App\Services\InvoiceEmailService;
use App\Models\InvoiceEmailBatch;
use App\Models\InvoiceEmailDelivery;
use App\Models\Invoice;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class InvoiceEmailController extends Controller
{
    public function __construct(
        private InvoiceEmailService $emailService
    ) {
        $this->middleware(['auth', 'rate.limit:default']);
        $this->middleware('can:view_payments')->except(['webhook']);
        $this->middleware('can:edit_payments')->only(['store', 'update', 'destroy', 'send', 'cancel']);
    }

    /**
     * Display a listing of email batches
     */
    public function index(Request $request): Response
    {
        $query = InvoiceEmailBatch::with(['creator', 'updater'])
            ->withCount('deliveries');

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by creator
        if ($request->filled('created_by')) {
            $query->byCreator($request->created_by);
        }

        // Search by name or batch number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('batch_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $batches = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Payments/Invoices/Email/Index', [
            'batches' => $batches,
            'filters' => $request->only(['status', 'date_from', 'date_to', 'created_by', 'search']),
            'statusOptions' => [
                ['value' => 'draft', 'label' => 'Borrador'],
                ['value' => 'scheduled', 'label' => 'Programado'],
                ['value' => 'processing', 'label' => 'Procesando'],
                ['value' => 'completed', 'label' => 'Completado'],
                ['value' => 'failed', 'label' => 'Fallido'],
                ['value' => 'cancelled', 'label' => 'Cancelado'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new email batch
     */
    public function create(): Response
    {
        $apartments = Apartment::with(['residents' => function ($query) {
            $query->where('email_notifications', true)->orWhere('resident_type', 'Owner');
        }])->select('id', 'number', 'floor', 'tower', 'apartment_type_id', 'status')->orderBy('number')->get();

        $invoicePeriods = Invoice::selectRaw('billing_period_year, billing_period_month, COUNT(*) as invoice_count')
            ->groupBy('billing_period_year', 'billing_period_month')
            ->orderByDesc('billing_period_year')
            ->orderByDesc('billing_period_month')
            ->get()
            ->map(function ($period) {
                return [
                    'year' => $period->billing_period_year,
                    'month' => $period->billing_period_month,
                    'label' => Carbon::createFromDate($period->billing_period_year, $period->billing_period_month, 1)->format('F Y'),
                    'invoice_count' => $period->invoice_count,
                ];
            });

        // Load eligible invoices (invoices that have apartments with residents with email)
        $eligibleInvoices = Invoice::with([
            'apartment' => function ($query) {
                $query->select('id', 'number', 'floor', 'tower', 'apartment_type_id');
            },
            'apartment.residents' => function ($query) {
                $query->whereNotNull('email')
                      ->where(function ($q) {
                          $q->where('email_notifications', true)
                            ->orWhere('resident_type', 'Owner');
                      })
                      ->select('id', 'apartment_id', 'first_name', 'last_name', 'email', 'resident_type', 'email_notifications');
            }
        ])
        ->whereHas('apartment.residents', function ($query) {
            $query->whereNotNull('email')
                  ->where(function ($q) {
                      $q->where('email_notifications', true)
                        ->orWhere('resident_type', 'Owner');
                  });
        })
        ->orderBy('invoice_number')
        ->get()
        ->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'apartment_number' => $invoice->apartment->number,
                'apartment_id' => $invoice->apartment_id,
                'status' => $invoice->status,
                'type' => $invoice->type ?? 'monthly',
                'total_amount' => $invoice->total_amount,
                'due_date' => $invoice->due_date,
                'billing_period_year' => $invoice->billing_period_year,
                'billing_period_month' => $invoice->billing_period_month,
                'billing_period_label' => $invoice->billing_period_label,
                'recipients' => $invoice->apartment->residents->map(function ($resident) {
                    return [
                        'name' => $resident->full_name,
                        'email' => $resident->email,
                        'type' => $resident->resident_type,
                    ];
                })->toArray(),
            ];
        });

        return Inertia::render('Payments/Invoices/Email/Create', [
            'apartments' => $apartments,
            'invoicePeriods' => $invoicePeriods,
            'eligibleInvoices' => [
                'data' => $eligibleInvoices,
                'total' => $eligibleInvoices->count(),
            ],
            'invoiceTypes' => [
                ['value' => 'monthly', 'label' => 'Administración mensual'],
                ['value' => 'individual', 'label' => 'Factura individual'],
                ['value' => 'late_fee', 'label' => 'Intereses de mora'],
            ],
            'invoiceStatuses' => [
                ['value' => 'pending', 'label' => 'Pendiente'],
                ['value' => 'paid', 'label' => 'Pagado'],
            ],
        ]);
    }

    /**
     * Store a newly created email batch
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'filters' => 'required|array',
            'filters.apartment_ids' => 'nullable|array',
            'filters.apartment_ids.*' => 'integer|exists:apartments,id',
            'filters.invoice_periods' => 'nullable|array',
            'filters.invoice_periods.*.year' => 'integer|min:2020|max:2030',
            'filters.invoice_periods.*.month' => 'integer|min:1|max:12',
            'filters.invoice_types' => 'nullable|array',
            'filters.invoice_types.*' => 'string|in:monthly,individual,late_fee',
            'filters.statuses' => 'nullable|array',
            'filters.statuses.*' => 'string|in:pendiente,pago_parcial,vencido,pagado',
            'filters.amount_min' => 'nullable|numeric|min:0',
            'filters.amount_max' => 'nullable|numeric|min:0',
            'filters.due_date_from' => 'nullable|date',
            'filters.due_date_to' => 'nullable|date|after_or_equal:filters.due_date_from',
            'email_settings' => 'nullable|array',
            'email_settings.subject' => 'nullable|string|max:255',
            'email_settings.template' => 'nullable|string|max:50',
            'email_settings.sender_name' => 'nullable|string|max:255',
            'email_settings.include_pdf' => 'nullable|boolean',
            'schedule_send' => 'nullable|boolean',
            'scheduled_at' => 'nullable|required_if:schedule_send,true|date|after:now',
        ]);

        try {
            $batch = $this->emailService->createBatch($validated);

            if ($validated['schedule_send'] ?? false) {
                $scheduledAt = Carbon::parse($validated['scheduled_at']);
                $this->emailService->scheduleBatch($batch, $scheduledAt);
            }

            return response()->json([
                'message' => 'Lote de emails creado exitosamente',
                'batch' => $batch->load('creator'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating email batch', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'data' => $validated
            ]);

            return response()->json([
                'message' => 'Error creando el lote de emails: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified email batch
     */
    public function show(InvoiceEmailBatch $batch): Response
    {
        $batch->load([
            'creator',
            'updater',
            'deliveries' => function ($query) {
                $query->with(['invoice', 'apartment'])
                      ->orderBy('created_at', 'desc');
            }
        ]);

        $statistics = $this->emailService->getBatchStatistics($batch);

        return Inertia::render('Payments/Invoices/Email/Show', [
            'batch' => $batch,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Update the specified email batch
     */
    public function update(Request $request, InvoiceEmailBatch $batch): JsonResponse
    {
        if (!$batch->is_editable) {
            return response()->json([
                'message' => 'El lote no puede ser editado en su estado actual',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'filters' => 'sometimes|required|array',
            'email_settings' => 'sometimes|array',
        ]);

        try {
            $batch->update(array_merge($validated, [
                'updated_by' => auth()->id(),
            ]));

            $batch->addToProcessingLog('Lote actualizado', [
                'updated_fields' => array_keys($validated),
                'updated_by' => auth()->user()->name,
            ]);

            return response()->json([
                'message' => 'Lote actualizado exitosamente',
                'batch' => $batch->fresh(['creator', 'updater']),
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Error actualizando el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified email batch
     */
    public function destroy(InvoiceEmailBatch $batch): JsonResponse
    {
        if (!$batch->is_editable) {
            return response()->json([
                'message' => 'El lote no puede ser eliminado en su estado actual',
            ], 422);
        }

        try {
            $batch->delete();

            return response()->json([
                'message' => 'Lote eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Error eliminando el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview invoices for a batch
     */
    public function previewInvoices(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filters' => 'required|array',
        ]);

        try {
            $invoices = $this->emailService->previewBatchInvoices($validated['filters']);
            $count = $this->emailService->countBatchInvoices($validated['filters']);
            $validation = $this->emailService->validateRecipients($validated['filters']);

            return response()->json([
                'invoices' => $invoices,
                'total_count' => $count,
                'validation' => $validation,
            ]);

        } catch (\Exception $e) {
            Log::error('Error previewing invoices', [
                'error' => $e->getMessage(),
                'filters' => $validated['filters'],
            ]);

            return response()->json([
                'message' => 'Error obteniendo la vista previa: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a batch immediately
     */
    public function send(InvoiceEmailBatch $batch): JsonResponse
    {
        if (!in_array($batch->status, ['draft', 'scheduled'])) {
            return response()->json([
                'message' => 'El lote no puede ser enviado en su estado actual',
            ], 422);
        }

        try {
            // If it's a draft, prepare it first
            if ($batch->status === 'draft') {
                if (!$this->emailService->prepareBatch($batch)) {
                    return response()->json([
                        'message' => 'Error preparando el lote para envío',
                    ], 500);
                }
                $batch->refresh();
            }

            // Queue the batch for processing
            dispatch(function () use ($batch) {
                $this->emailService->processBatch($batch);
            })->onQueue('emails');

            return response()->json([
                'message' => 'Lote puesto en cola para envío',
                'batch' => $batch->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Error enviando el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a batch
     */
    public function cancel(InvoiceEmailBatch $batch, Request $request): JsonResponse
    {
        if (!$batch->can_be_cancelled) {
            return response()->json([
                'message' => 'El lote no puede ser cancelado en su estado actual',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $this->emailService->cancelBatch($batch, $validated['reason'] ?? null);

            return response()->json([
                'message' => 'Lote cancelado exitosamente',
                'batch' => $batch->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error cancelling email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Error cancelando el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed deliveries in a batch
     */
    public function retry(InvoiceEmailBatch $batch): JsonResponse
    {
        if (!$batch->can_be_restarted) {
            return response()->json([
                'message' => 'El lote no puede ser reintentado en su estado actual',
            ], 422);
        }

        try {
            $retriableDeliveries = $batch->deliveries()
                ->needingRetry()
                ->count();

            if ($retriableDeliveries === 0) {
                return response()->json([
                    'message' => 'No hay envíos disponibles para reintentar',
                ], 422);
            }

            // Reset batch status and retry failed deliveries
            $batch->update([
                'status' => 'scheduled',
                'scheduled_at' => now(),
            ]);

            $batch->deliveries()
                ->needingRetry()
                ->get()
                ->each(function ($delivery) {
                    $delivery->scheduleRetry();
                });

            dispatch(function () use ($batch) {
                $this->emailService->processBatch($batch);
            })->onQueue('emails');

            return response()->json([
                'message' => "Reintentando {$retriableDeliveries} envíos",
                'batch' => $batch->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrying email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Error reintentando el lote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get delivery details for a batch
     */
    public function deliveries(InvoiceEmailBatch $batch, Request $request): JsonResponse
    {
        $query = $batch->deliveries()
            ->with(['invoice', 'apartment']);

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Search by email or apartment
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recipient_email', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('apartment_number', 'like', "%{$search}%");
            });
        }

        $deliveries = $query->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();

        return response()->json($deliveries);
    }

    /**
     * Handle webhooks from email service providers
     */
    public function webhook(Request $request, string $provider): JsonResponse
    {
        try {
            // This would handle webhooks from email services like SendGrid, SES, etc.
            // to update delivery status based on provider events
            
            Log::info('Email webhook received', [
                'provider' => $provider,
                'payload' => $request->all(),
            ]);

            // Process webhook based on provider
            $this->processProviderWebhook($provider, $request->all());

            return response()->json(['status' => 'ok']);

        } catch (\Exception $e) {
            Log::error('Error processing email webhook', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process webhook events from email service providers
     */
    private function processProviderWebhook(string $provider, array $payload): void
    {
        // This would be implemented based on the specific email service provider
        // Each provider has different webhook formats and event types
        
        // Example implementation structure:
        // - Extract message ID and event type from payload
        // - Find the corresponding InvoiceEmailDelivery record
        // - Update the delivery status based on the event type
        // - Log the event for auditing
    }
}