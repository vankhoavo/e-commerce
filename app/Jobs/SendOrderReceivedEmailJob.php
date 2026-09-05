<?php

namespace App\Jobs;

use App\Mail\OrderReceivedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderReceivedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $orderId,
        public readonly string $recipient,
    ) {
        // Laravel Cloud currently provides the Managed Queue as `default`.
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $order = Order::query()->with('items')->find($this->orderId);

        if (! $order) {
            return;
        }

        try {
            Mail::to($this->recipient)->send(new OrderReceivedMail($order));
        } catch (\Throwable $exception) {
            Log::warning('Không thể gửi Email xác nhận đơn hàng.', [
                'order_id' => $order->id,
                'recipient' => $this->recipient,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
