<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceEmailBatch;
use App\Models\InvoiceEmailDelivery;
use App\Models\InvoiceEmailSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceEmailService
{
    private array $defaultEmailSettings;

    public function __construct()
    {
        $this->defaultEmailSettings = [
            'template' => 'invoice',
            'subject' => 'Factura de Administración - {{invoice_number}}',
            'sender_name' => config('app.name', 'Habitta'),
            'reply_to' => config('mail.from.address'),
            'include_pdf' => true,
            'include_payment_link' => false,
        ];
    }

    /**
     * Create a new email batch for invoices
     */
    public function createBatch(array $data): InvoiceEmailBatch
    {
        DB::beginTransaction();

        try {
            // Calculate total invoices based on filters
            $totalInvoices = $this->countBatchInvoices($data['filters'] ?? []);

            $batch = InvoiceEmailBatch::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'filters' => $data['filters'] ?? [],
                'email_settings' => array_merge($this->defaultEmailSettings, $data['email_settings'] ?? []),
                'total_invoices' => $totalInvoices,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            $batch->addToProcessingLog('Lote creado', [
                'filters' => $data['filters'] ?? [],
                'email_settings' => $data['email_settings'] ?? [],
                'total_invoices' => $totalInvoices,
            ]);

            DB::commit();

            return $batch;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating invoice email batch', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Preview the invoices that will be included in a batch
     */
    public function previewBatchInvoices(array $filters): Collection
    {
        $query = $this->buildInvoiceQuery($filters);

        return $query->with([
            'apartment',
            'apartment.residents' => function ($q) {
                $q->where('resident_type', 'Owner')->orWhere('email_notifications', true);
            },
        ])->limit(100)->get();
    }

    /**
     * Get the count of invoices that match the filters
     */
    public function countBatchInvoices(array $filters): int
    {
        return $this->buildInvoiceQuery($filters)->count();
    }

    /**
     * Validate recipients for a batch
     */
    public function validateRecipients(array $filters): array
    {
        $invoices = $this->previewBatchInvoices($filters);
        $summary = [
            'total_invoices' => $invoices->count(),
            'valid_recipients' => 0,
            'invalid_recipients' => 0,
            'missing_emails' => 0,
            'duplicate_emails' => 0,
            'recipients' => [],
            'issues' => [],
        ];

        $emailMap = [];

        foreach ($invoices as $invoice) {
            $recipients = $this->getInvoiceRecipients($invoice);

            if (empty($recipients)) {
                $summary['missing_emails']++;
                $summary['issues'][] = [
                    'type' => 'no_email',
                    'invoice_id' => $invoice->id,
                    'apartment_number' => $invoice->apartment->number,
                    'message' => 'No se encontró email válido para el apartamento',
                ];

                continue;
            }

            foreach ($recipients as $recipient) {
                $email = strtolower($recipient['email']);

                if (! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                    $summary['invalid_recipients']++;
                    $summary['issues'][] = [
                        'type' => 'invalid_email',
                        'invoice_id' => $invoice->id,
                        'apartment_number' => $invoice->apartment->number,
                        'email' => $recipient['email'],
                        'message' => 'Formato de email inválido',
                    ];

                    continue;
                }

                if (isset($emailMap[$email])) {
                    $summary['duplicate_emails']++;
                    $summary['issues'][] = [
                        'type' => 'duplicate_email',
                        'invoice_id' => $invoice->id,
                        'apartment_number' => $invoice->apartment->number,
                        'email' => $recipient['email'],
                        'previous_apartment' => $emailMap[$email]['apartment_number'],
                        'message' => 'Email duplicado en múltiples apartamentos',
                    ];
                } else {
                    $summary['valid_recipients']++;
                    $emailMap[$email] = [
                        'invoice_id' => $invoice->id,
                        'apartment_number' => $invoice->apartment->number,
                    ];
                }

                $summary['recipients'][] = [
                    'invoice_id' => $invoice->id,
                    'apartment_number' => $invoice->apartment->number,
                    'email' => $recipient['email'],
                    'name' => $recipient['name'],
                    'is_valid' => true,
                ];
            }
        }

        return $summary;
    }

    /**
     * Prepare a batch for sending
     */
    public function prepareBatch(InvoiceEmailBatch $batch): bool
    {
        if ($batch->status !== 'draft') {
            throw new \InvalidArgumentException('Solo los lotes en borrador pueden ser preparados');
        }

        DB::beginTransaction();

        try {
            $batch->addToProcessingLog('Iniciando preparación del lote');

            // Get invoices based on filters
            $invoices = $this->buildInvoiceQuery($batch->filters)
                ->with(['apartment', 'apartment.residents'])
                ->get();

            if ($invoices->isEmpty()) {
                $batch->markAsFailed('No se encontraron facturas que coincidan con los filtros');
                DB::rollBack();

                return false;
            }

            $batch->addToProcessingLog('Facturas encontradas', ['count' => $invoices->count()]);

            // Create delivery records
            $deliveries = [];
            $totalCost = 0;

            foreach ($invoices as $invoice) {
                $recipients = $this->getInvoiceRecipients($invoice);

                foreach ($recipients as $recipient) {
                    if (! filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                        continue;
                    }

                    $delivery = [
                        'batch_id' => $batch->id,
                        'invoice_id' => $invoice->id,
                        'apartment_id' => $invoice->apartment_id,
                        'recipient_email' => $recipient['email'],
                        'recipient_name' => $recipient['name'],
                        'apartment_number' => $invoice->apartment->number,
                        'email_subject' => $this->parseTemplate($batch->email_settings['subject'], $invoice, $recipient),
                        'email_template_used' => $batch->email_settings['template'],
                        'email_variables' => $this->getTemplateVariables($invoice, $recipient),
                        'attachments' => $this->getAttachmentsList($invoice, $batch->email_settings),
                        'status' => 'pending',
                        'cost' => $this->calculateEmailCost(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $deliveries[] = $delivery;
                    $totalCost += $delivery['cost'];
                }
            }

            if (empty($deliveries)) {
                $batch->markAsFailed('No se encontraron destinatarios válidos');
                DB::rollBack();

                return false;
            }

            // Create deliveries individually to ensure proper casting
            foreach ($deliveries as $deliveryData) {
                InvoiceEmailDelivery::create($deliveryData);
            }

            // Update batch statistics
            $batch->update([
                'total_invoices' => $invoices->count(),
                'total_recipients' => count($deliveries),
                'estimated_cost' => $totalCost,
            ]);

            $batch->addToProcessingLog('Lote preparado exitosamente', [
                'total_invoices' => $invoices->count(),
                'total_recipients' => count($deliveries),
                'estimated_cost' => $totalCost,
            ]);

            DB::commit();

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            $batch->markAsFailed('Error preparando el lote: '.$e->getMessage());
            Log::error('Error preparing email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Schedule a batch for sending
     */
    public function scheduleBatch(InvoiceEmailBatch $batch, ?Carbon $scheduledAt = null): bool
    {
        if ($batch->status !== 'draft') {
            throw new \InvalidArgumentException('Solo los lotes en borrador pueden ser programados');
        }

        if ($batch->total_recipients === 0) {
            if (! $this->prepareBatch($batch)) {
                return false;
            }
            $batch->refresh();
        }

        $scheduledAt = $scheduledAt ?? now();

        $batch->markAsScheduled($scheduledAt);

        $batch->addToProcessingLog('Lote programado para envío', [
            'scheduled_at' => $scheduledAt->toISOString(),
        ]);

        return true;
    }

    /**
     * Process a batch and send emails
     */
    public function processBatch(InvoiceEmailBatch $batch): bool
    {
        if (! in_array($batch->status, ['scheduled', 'draft'])) {
            throw new \InvalidArgumentException('El lote no está en estado válido para procesamiento');
        }

        $batch->markAsStarted();
        $batch->addToProcessingLog('Iniciando procesamiento del lote');

        try {
            $deliveries = $batch->deliveries()
                ->where('status', 'pending')
                ->orderBy('id')
                ->get();

            if ($deliveries->isEmpty()) {
                $batch->markAsCompleted();

                return true;
            }

            $processed = 0;
            $failed = 0;

            foreach ($deliveries as $delivery) {
                try {
                    $this->sendSingleEmail($delivery);
                    $processed++;

                    // Add small delay to avoid overwhelming email service
                    usleep(100000); // 0.1 seconds

                } catch (\Exception $e) {
                    $delivery->markAsFailed('Error enviando email: '.$e->getMessage());
                    $failed++;
                    Log::error('Error sending single email', [
                        'delivery_id' => $delivery->id,
                        'batch_id' => $batch->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Update batch statistics periodically
                if (($processed + $failed) % 10 === 0) {
                    $batch->updateStatistics();
                }
            }

            $batch->updateStatistics();
            $batch->markAsCompleted();

            $batch->addToProcessingLog('Lote procesado completamente', [
                'processed' => $processed,
                'failed' => $failed,
            ]);

            return true;

        } catch (\Exception $e) {
            $batch->markAsFailed('Error procesando el lote: '.$e->getMessage());
            Log::error('Error processing email batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a single email delivery
     */
    public function sendSingleEmail(InvoiceEmailDelivery $delivery): bool
    {
        $delivery->markAsSending();

        try {
            $invoice = $delivery->invoice;

            // Generate PDF content (for now, use dummy content)
            $pdfContent = "PDF content for invoice {$invoice->invoice_number}"; // This would be actual PDF generation

            // Generate PDF if required
            if ($delivery->email_variables['include_pdf'] ?? true) {
                // This would integrate with existing PDF generation
                // $pdfContent = $this->generateInvoicePdf($invoice);
            }

            Mail::to($delivery->recipient_email, $delivery->recipient_name)
                ->send(new InvoiceMail($invoice, $pdfContent));

            $delivery->markAsSent(
                provider: config('mail.default'),
                messageId: null, // Would be populated by mail service webhook
                metadata: [
                    'sent_via' => 'direct_send',
                    'timestamp' => now()->toISOString(),
                ]
            );

            return true;

        } catch (\Exception $e) {
            $delivery->markAsFailed('Error enviando email: '.$e->getMessage());
            Log::error('Error sending email delivery', [
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancel a batch
     */
    public function cancelBatch(InvoiceEmailBatch $batch, ?string $reason = null): bool
    {
        if (! $batch->can_be_cancelled) {
            throw new \InvalidArgumentException('El lote no puede ser cancelado en su estado actual');
        }

        // Cancel pending deliveries
        $batch->deliveries()
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'failure_reason' => 'Lote cancelado: '.($reason ?? 'Sin razón especificada'),
                'failed_at' => now(),
            ]);

        $batch->markAsCancelled($reason);

        $batch->addToProcessingLog('Lote cancelado', [
            'reason' => $reason,
            'cancelled_by' => auth()->user()->name ?? 'Sistema',
        ]);

        return true;
    }

    /**
     * Get email statistics for a batch
     */
    public function getBatchStatistics(InvoiceEmailBatch $batch): array
    {
        $stats = $batch->deliveries()
            ->selectRaw('
                status,
                COUNT(*) as count,
                AVG(cost) as avg_cost,
                SUM(cost) as total_cost
            ')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $failureReasons = $batch->deliveries()
            ->whereNotNull('failure_reason')
            ->selectRaw('failure_reason, COUNT(*) as count')
            ->groupBy('failure_reason')
            ->get();

        return [
            'status_breakdown' => $stats->toArray(),
            'failure_reasons' => $failureReasons->toArray(),
            'delivery_rate' => $batch->delivery_rate,
            'open_rate' => $batch->open_rate,
            'click_rate' => $batch->click_rate,
            'total_cost' => $batch->actual_cost,
            'avg_cost_per_email' => $batch->total_recipients > 0 ? $batch->actual_cost / $batch->total_recipients : 0,
        ];
    }

    /**
     * Build query for invoices based on filters
     */
    private function buildInvoiceQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Invoice::query();

        // Handle specific invoice IDs first (highest priority)
        if (! empty($filters['invoice_ids'])) {
            $query->whereIn('id', $filters['invoice_ids']);

            return $query; // Return early if specific IDs are provided
        }

        if (! empty($filters['apartment_ids'])) {
            $query->whereIn('apartment_id', $filters['apartment_ids']);
        }

        if (! empty($filters['invoice_periods'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['invoice_periods'] as $period) {
                    $q->orWhere(function ($subQ) use ($period) {
                        $subQ->where('billing_period_year', $period['year'])
                            ->where('billing_period_month', $period['month']);
                    });
                }
            });
        }

        if (! empty($filters['invoice_types'])) {
            $query->whereIn('type', $filters['invoice_types']);
        }

        if (! empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (! empty($filters['amount_min'])) {
            $query->where('total_amount', '>=', $filters['amount_min']);
        }

        if (! empty($filters['amount_max'])) {
            $query->where('total_amount', '<=', $filters['amount_max']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        return $query;
    }

    /**
     * Get recipients for an invoice
     */
    private function getInvoiceRecipients(Invoice $invoice): array
    {
        $recipients = [];

        foreach ($invoice->apartment->residents as $resident) {
            if ($resident->email && ($resident->resident_type === 'Owner' || $resident->email_notifications)) {
                $recipients[] = [
                    'email' => $resident->email,
                    'name' => $resident->full_name,
                    'type' => $resident->resident_type === 'Owner' ? 'owner' : 'resident',
                ];
            }
        }

        return $recipients;
    }

    /**
     * Parse template with variables
     */
    private function parseTemplate(string $template, Invoice $invoice, array $recipient): string
    {
        $variables = $this->getTemplateVariables($invoice, $recipient);

        foreach ($variables as $key => $value) {
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }

        return $template;
    }

    /**
     * Get template variables for an invoice
     */
    private function getTemplateVariables(Invoice $invoice, array $recipient): array
    {
        return [
            'invoice_number' => $invoice->invoice_number,
            'apartment_number' => $invoice->apartment->number,
            'resident_name' => $recipient['name'],
            'total_amount' => number_format($invoice->total_amount, 2),
            'due_date' => $invoice->due_date->format('d/m/Y'),
            'billing_period' => $invoice->billing_period_label,
            'company_name' => config('app.name'),
        ];
    }

    /**
     * Get list of attachments for an email
     */
    private function getAttachmentsList(Invoice $invoice, array $emailSettings): array
    {
        $attachments = [];

        if ($emailSettings['include_pdf'] ?? true) {
            $attachments[] = [
                'name' => "Factura-{$invoice->invoice_number}.pdf",
                'type' => 'pdf',
                'path' => null, // Would be generated dynamically
            ];
        }

        return $attachments;
    }

    /**
     * Calculate cost per email
     */
    private function calculateEmailCost(): float
    {
        return InvoiceEmailSetting::get('cost_per_email', 0.001);
    }
}
