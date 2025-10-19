<?php

namespace App\Mail;

use App\Models\Central\Provider;
use App\Models\QuotationRequest;
use App\Models\QuotationResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationResponseReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public QuotationRequest $quotationRequest,
        public QuotationResponse $response,
        public Provider $provider,
        public string $tenantId,
        public string $tenantName
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva Propuesta Recibida - '.$this->quotationRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quotation-response-received',
            with: [
                'quotationRequest' => $this->quotationRequest,
                'response' => $this->response,
                'provider' => $this->provider,
                'tenantId' => $this->tenantId,
                'tenantName' => $this->tenantName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
