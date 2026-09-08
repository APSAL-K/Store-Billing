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

    /**
     * The order id rather than the model itself: by the time the worker picks
     * this up the order may have been touched, and we want the current state.
     */
    public function __construct(public readonly int $orderId) {}

    public function handle(): void
    {
        $order = Order::with(['customer', 'items.product'])->find($this->orderId);

        if ($order === null) {
            // The order was rolled back or removed before the worker got here.
            // Nothing to send, and nothing worth retrying.
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
