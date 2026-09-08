<?php

namespace Database\Seeders;

use App\Data\NewOrderData;
use App\Data\OrderLineData;
use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrderSeeder extends Seeder
{
    public function __construct(private readonly OrderService $orders) {}

    public function run(): void
    {
        $customers = Customer::whereIn('email', ['thomas@example.com', 'divya@example.com'])->get();
        $sellable = Product::where('stock_on_hand', '>=', 20)->get();

        if ($customers->isEmpty() || $sellable->count() < 3) {
            return;
        }

        foreach (range(1, 12) as $index) {
            $customer = $customers->random();
            $lines = $sellable->random(random_int(1, 3))
                ->map(fn (Product $product) => new OrderLineData($product->id, random_int(1, 3)));

            $order = $this->orders->place(new NewOrderData(
                customerEmail: $customer->email,
                customerName: $customer->name,
                lines: $lines,
                amountTendered: null,
            ));

            $order->forceFill([
                'placed_at' => Carbon::now()->subDays(30 - $index * 2)->subHours(random_int(0, 9)),
            ])->save();
        }
    }
}
