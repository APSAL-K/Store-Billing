<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestockProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_stock_and_records_the_movement(): void
    {
        $product = Product::factory()->withStock(4)->create();

        $this->postJson("/api/products/{$product->id}/restock", [
            'quantity' => 50,
            'note' => 'Supplier invoice 4471',
        ])->assertOk()->assertJsonPath('data.stock_on_hand', 54);

        $this->assertSame(54, $product->refresh()->stock_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'order_id' => null,
            'reason' => StockMovement::REASON_RESTOCK,
            'quantity_change' => 50,
            'balance_after' => 54,
            'note' => 'Supplier invoice 4471',
        ]);
    }

    public function test_a_restock_brings_a_product_back_out_of_the_low_stock_list(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        $product = Product::factory()->outOfStock()->create();

        $this->getJson('/api/products/low-stock')->assertJsonCount(1, 'data');

        $this->postJson("/api/products/{$product->id}/restock", ['quantity' => 40])->assertOk();

        $this->getJson('/api/products/low-stock')->assertJsonCount(0, 'data');
    }

    public function test_it_rejects_a_nonsensical_quantity(): void
    {
        $product = Product::factory()->withStock(4)->create();

        $this->postJson("/api/products/{$product->id}/restock", ['quantity' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        $this->postJson("/api/products/{$product->id}/restock", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');

        $this->assertSame(4, $product->refresh()->stock_on_hand);
        $this->assertDatabaseCount('stock_movements', 0);
    }
}
