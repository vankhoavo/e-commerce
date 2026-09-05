<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\VatInvoicePdfService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VatInvoiceMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'TechStore - Hóa đơn VAT đơn hàng '.$this->order->code);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.vat-invoice');
    }

    public function attachments(): array
    {
        $pdf = app(VatInvoicePdfService::class);

        return [
            Attachment::fromData(fn () => $pdf->binary($this->order), $pdf->filename($this->order))
                ->withMime('application/pdf'),
        ];
    }
}
