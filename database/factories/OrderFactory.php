<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 50, 5000);
        $tax = round($subtotal * 0.18, 2);

        return [
            'customer_id' => Customer::factory(),
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'grand_total' => $subtotal + $tax,
            'amount_tendered' => null,
            'change_due' => null,
            'placed_at' => $this->faker->dateTimeBetween('-3 months'),
        ];
    }
}
