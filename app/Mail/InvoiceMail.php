<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public $pdfContent;

    public $template;

    public $processedTemplate;

    /**
     * Create a new message instance.
     */
    public function __construct(Invoice $invoice, $pdfContent, ?EmailTemplate $template = null)
    {
        $this->invoice = $invoice;
        $this->pdfContent = $pdfContent;

        // Use provided template or get default invoice template
        $this->template = $template ?: EmailTemplate::getDefaultForType('invoice');

        // Process template with invoice data if template exists
        if ($this->template) {
            $this->processedTemplate = $this->template->processTemplate([
                'invoice_number' => $this->invoice->invoice_number,
                'apartment_number' => $this->invoice->apartment->number,
                'apartment_address' => $this->invoice->apartment->full_address,
                'billing_period' => $this->invoice->billing_period_label,
                'due_date' => $this->invoice->due_date->format('d/m/Y'),
                'total_amount' => number_format($this->invoice->total_amount, 0, ',', '.'),
                'balance_due' => number_format($this->invoice->balance_due, 0, ',', '.'),
                'conjunto_name' => $this->invoice->apartment->conjuntoConfig->name ?? 'Conjunto Residencial',
                'billing_date' => $this->invoice->billing_date->format('d/m/Y'),
            ]);
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Use processed template subject if available, otherwise use default
        $subject = $this->processedTemplate['subject'] ??
                  "Factura #{$this->invoice->invoice_number} - {$this->invoice->apartment->full_address}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Use new template view if template exists, otherwise fallback to old view
        $view = $this->template ? 'emails.template' : 'emails.invoice';

        return new Content(
            view: $view,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "factura-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
