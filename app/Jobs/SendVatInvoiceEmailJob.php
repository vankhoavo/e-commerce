<?php

namespace App\Jobs;

use App\Mail\VatInvoiceMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVatInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $orderId,
        public readonly string $recipient,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $order = Order::query()->with('items')->find($this->orderId);

        if (! $order) {
            return;
        }

        try {
            Mail::to($this->recipient)->send(new VatInvoiceMail($order));
        } catch (\Throwable $exception) {
            Log::warning('Không thể gửi Email hóa đơn VAT.', [
                'order_id' => $order->id,
                'recipient' => $this->recipient,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
