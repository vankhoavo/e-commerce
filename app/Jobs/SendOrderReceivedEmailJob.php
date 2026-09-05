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
        Log::info('EMAIL_ORDER_JOB: started.', [
            'order_id' => $this->orderId,
            'recipient' => $this->recipient,
            'queue' => $this->queue,
        ]);

        $order = Order::query()->with('items')->find($this->orderId);

        if (! $order) {
            Log::warning('EMAIL_ORDER_JOB: order not found.', [
                'order_id' => $this->orderId,
            ]);
            return;
        }

        try {
            Log::info('EMAIL_ORDER_JOB: sending SMTP email.', [
                'order_id' => $order->id,
                'recipient' => $this->recipient,
            ]);

            Mail::to($this->recipient)->send(new OrderReceivedMail($order));

            Log::info('EMAIL_ORDER_JOB: SMTP send completed.', [
                'order_id' => $order->id,
                'recipient' => $this->recipient,
            ]);
        } catch (\Throwable $exception) {
            Log::error('EMAIL_ORDER_JOB: SMTP send failed.', [
                'order_id' => $order->id,
                'recipient' => $this->recipient,
                'exception' => get_class($exception),
                'error' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EMAIL_ORDER_JOB: permanently failed.', [
            'order_id' => $this->orderId,
            'recipient' => $this->recipient,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);
    }
}
