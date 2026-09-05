<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\VatInvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class VatInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->onQueue('emails');
    }

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
