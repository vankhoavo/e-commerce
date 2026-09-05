<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderReceivedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'TechStore - Đơn hàng '.$this->order->code.' đang chờ xử lý',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.orders.received');
    }
}
