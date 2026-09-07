<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('??-####')),
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'unit_price' => $this->faker->randomFloat(2, 5, 900),
            'tax_percentage' => $this->faker->randomElement([0, 5, 12, 18]),
            'stock_on_hand' => $this->faker->numberBetween(0, 200),
            'low_stock_threshold' => null,
        ];
    }

    public function withStock(int $units): static
    {
        return $this->state(fn (): array => ['stock_on_hand' => $units]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock_on_hand' => 0]);
    }

    public function taxFree(): static
    {
        return $this->state(fn (): array => ['tax_percentage' => 0]);
    }
}
