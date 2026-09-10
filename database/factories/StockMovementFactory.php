<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $change = $this->faker->numberBetween(-8, -1);

        return [
            'product_id' => Product::factory(),
            'order_id' => null,
            'reason' => StockMovement::REASON_SALE,
            'note' => null,
            'quantity_change' => $change,
            'balance_after' => $this->faker->numberBetween(0, 200),
        ];
    }

    public function restock(int $units = 50): static
    {
        return $this->state(fn (): array => [
            'reason' => StockMovement::REASON_RESTOCK,
            'quantity_change' => $units,
        ]);
    }
}
