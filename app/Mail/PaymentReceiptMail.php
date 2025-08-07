<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public array $data
    ) {}

    public function envelope(): Envelope
    {
        $payment = $this->data['payment'];

        return new Envelope(
            subject: "Comprobante de Pago {$payment->payment_number} - Apartamento {$payment->apartment->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: [
                'payment' => $this->data['payment'],
                'includeApplications' => $this->data['includeApplications'] ?? false,
                'customMessage' => $this->data['customMessage'] ?? null,
            ]
        );
    }
}
