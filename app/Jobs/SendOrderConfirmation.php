<?php

namespace App\Jobs;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::with(['customer', 'items.product'])->find($this->orderId);

        if ($order === null) {
            return;
        }

        Mail::to($order->customer->email)->send(new OrderConfirmation($order));

        Log::info('Order confirmation queued for delivery', [
            'order_id' => $order->id,
            'reference' => $order->reference,
            'customer_email' => $order->customer->email,
            'grand_total' => $order->grand_total,
        ]);
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }
}
