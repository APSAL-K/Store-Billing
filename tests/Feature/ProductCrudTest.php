<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maggi Noodles 70g',
            'code' => 'snk-2010',
            'unit_price' => 14.00,
            'tax_percentage' => 12,
            'stock_on_hand' => 80,
            'low_stock_threshold' => 25,
        ], $overrides);
    }

    public function test_it_creates_a_product_and_upper_cases_the_code(): void
    {
        $this->postJson('/api/products', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.code', 'SNK-2010')
            ->assertJsonPath('data.stock_on_hand', 80)
            ->assertJsonPath('data.low_stock_threshold', 25);

        $this->assertDatabaseHas('products', ['code' => 'SNK-2010', 'unit_price' => '14.00']);
    }

    public function test_it_refuses_a_duplicate_code(): void
    {
        Product::factory()->create(['code' => 'SNK-2010']);

        $this->postJson('/api/products', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_it_validates_the_shape(): void
    {
        $this->postJson('/api/products', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'unit_price', 'tax_percentage', 'stock_on_hand']);

        $this->postJson('/api/products', $this->payload(['code' => 'has spaces']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->postJson('/api/products', $this->payload(['unit_price' => -1]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit_price');

        $this->postJson('/api/products', $this->payload(['tax_percentage' => 140]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tax_percentage');
    }

    public function test_it_updates_a_product_without_tripping_its_own_code(): void
    {
        $product = Product::factory()->create(['code' => 'SNK-2010', 'unit_price' => 14.00]);

        $this->putJson("/api/products/{$product->id}", [
            'name' => 'Maggi Noodles 70g',
            'code' => 'SNK-2010',
            'unit_price' => 15.50,
            'tax_percentage' => 12,
        ])->assertOk()->assertJsonPath('data.unit_price', '15.50');

        $this->assertSame('15.50', $product->refresh()->unit_price);
    }

    /**
     * Stock is an outcome of bills and restocks; letting it be typed here would
     * put the ledger and the shelf out of step with no movement explaining it.
     */
    public function test_an_update_cannot_set_stock_directly(): void
    {
        $product = Product::factory()->withStock(10)->create(['code' => 'SNK-2010']);

        $this->putJson("/api/products/{$product->id}", [
            'name' => $product->name,
            'code' => 'SNK-2010',
            'unit_price' => 14.00,
            'tax_percentage' => 12,
            'stock_on_hand' => 9999,
        ])->assertStatus(422)->assertJsonValidationErrors('stock_on_hand');

        $this->assertSame(10, $product->refresh()->stock_on_hand);
    }

    public function test_repricing_a_product_leaves_bills_already_raised_alone(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create([
            'code' => 'SNK-2010',
            'unit_price' => 100.00,
            'stock_on_hand' => 20,
        ]);

        $orderId = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        $this->putJson("/api/products/{$product->id}", [
            'name' => $product->name,
            'code' => 'SNK-2010',
            'unit_price' => 250.00,
            'tax_percentage' => 0,
        ])->assertOk();

        $order = Order::find($orderId);

        $this->assertSame('200.00', $order->grand_total);
        $this->assertSame('100.00', $order->items()->first()->unit_price);
    }

    public function test_it_soft_deletes_a_product_and_leaves_its_history_readable(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['name' => 'Maggi Noodles 70g', 'stock_on_hand' => 20]);

        $orderId = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated()->json('data.id');

        $this->deleteJson("/api/products/{$product->id}")->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0, 'data');

        $this->get("/orders/{$orderId}")->assertOk()->assertSee('Maggi Noodles 70g');
        $this->get("/products/{$product->id}")->assertNotFound();
    }

    public function test_a_deleted_product_cannot_be_sold_again(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $product->delete();

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.product_id');
    }
}
