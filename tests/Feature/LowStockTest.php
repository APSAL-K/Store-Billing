<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_configured_default_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        $low = Product::factory()->withStock(4)->create();
        $onTheLine = Product::factory()->withStock(10)->create();
        Product::factory()->withStock(11)->create();

        $response = $this->getJson('/api/products/low-stock')->assertOk()->assertJsonCount(2, 'data');

        $this->assertEqualsCanonicalizing(
            [$low->id, $onTheLine->id],
            array_column($response->json('data'), 'id')
        );
    }

    public function test_a_product_can_override_the_default_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        // Below the product's own threshold but comfortably above the default.
        $fastMover = Product::factory()->withStock(30)->create(['low_stock_threshold' => 40]);
        Product::factory()->withStock(30)->create(['low_stock_threshold' => null]);

        $this->getJson('/api/products/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $fastMover->id)
            ->assertJsonPath('data.0.low_stock_threshold', 40);
    }

    public function test_an_explicit_threshold_overrides_everything_else(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        $almostOut = Product::factory()->withStock(2)->create(['low_stock_threshold' => 40]);
        Product::factory()->withStock(9)->create(['low_stock_threshold' => 40]);

        $this->getJson('/api/products/low-stock?threshold=3')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $almostOut->id);
    }

    public function test_it_lists_the_emptiest_shelves_first(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        Product::factory()->withStock(8)->create();
        Product::factory()->outOfStock()->create();
        Product::factory()->withStock(3)->create();

        $response = $this->getJson('/api/products/low-stock')->assertOk();

        $this->assertSame([0, 3, 8], array_column($response->json('data'), 'stock_on_hand'));
    }

    public function test_it_rejects_a_nonsensical_threshold(): void
    {
        $this->getJson('/api/products/low-stock?threshold=-1')
            ->assertStatus(422)
            ->assertJsonValidationErrors('threshold');
    }
}
