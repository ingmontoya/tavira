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

class QuotationResponseApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public QuotationRequest $quotationRequest,
        public QuotationResponse $quotationResponse,
        public Provider $provider,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Felicitaciones! Su cotización ha sido aprobada - '.$this->quotationRequest->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.quotation-response-approved',
            with: [
                'quotationRequest' => $this->quotationRequest,
                'quotationResponse' => $this->quotationResponse,
                'provider' => $this->provider,
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
