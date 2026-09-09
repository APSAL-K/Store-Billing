<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EditOrderTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(array $items, string $email = 'walkin@example.com'): int
    {
        return $this->postJson('/api/orders', [
            'customer' => ['email' => $email, 'name' => 'Walk-in Customer'],
            'items' => $items,
        ])->assertCreated()->json('data.id');
    }

    public function test_reducing_a_line_returns_the_difference_to_the_shelf(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 50, 'stock_on_hand' => 20]);

        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 8]]);
        $this->assertSame(12, $product->refresh()->stock_on_hand);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertOk()->assertJsonPath('data.totals.grand_total', '150.00');

        $this->assertSame(17, $product->refresh()->stock_on_hand);
    }

    public function test_increasing_a_line_takes_the_difference_off_the_shelf(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 50, 'stock_on_hand' => 20]);

        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 3]]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 9]],
        ])->assertOk();

        $this->assertSame(11, $product->refresh()->stock_on_hand);
    }

    public function test_a_removed_line_gives_all_of_its_units_back(): void
    {
        Queue::fake();

        $kept = Product::factory()->taxFree()->create(['unit_price' => 50, 'stock_on_hand' => 20]);
        $dropped = Product::factory()->taxFree()->create(['unit_price' => 30, 'stock_on_hand' => 20]);

        $orderId = $this->placeOrder([
            ['product_id' => $kept->id, 'quantity' => 2],
            ['product_id' => $dropped->id, 'quantity' => 4],
        ]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $kept->id, 'quantity' => 2]],
        ])->assertOk()->assertJsonCount(1, 'data.items');

        $this->assertSame(18, $kept->refresh()->stock_on_hand);
        $this->assertSame(20, $dropped->refresh()->stock_on_hand);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_an_edit_that_cannot_be_stocked_changes_nothing(): void
    {
        Queue::fake();

        $plenty = Product::factory()->taxFree()->create(['unit_price' => 50, 'stock_on_hand' => 20]);
        $scarce = Product::factory()->taxFree()->create(['unit_price' => 30, 'stock_on_hand' => 2]);

        $orderId = $this->placeOrder([['product_id' => $plenty->id, 'quantity' => 5]]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [
                ['product_id' => $plenty->id, 'quantity' => 1],
                ['product_id' => $scarce->id, 'quantity' => 99],
            ],
        ])->assertStatus(422)->assertJsonPath('shortages.0.available', 2);

        $this->assertSame(15, $plenty->refresh()->stock_on_hand);
        $this->assertSame(2, $scarce->refresh()->stock_on_hand);

        $order = Order::find($orderId);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(5, $order->items()->first()->quantity);
    }

    public function test_an_edit_records_its_stock_movements(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 50, 'stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 8]]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertOk();

        $this->assertDatabaseHas('stock_movements', [
            'order_id' => $orderId,
            'reason' => StockMovement::REASON_ADJUSTMENT,
            'quantity_change' => 5,
            'balance_after' => 17,
        ]);
    }

    public function test_an_edit_can_move_the_bill_to_another_customer(): void
    {
        Queue::fake();

        Customer::factory()->create(['email' => 'divya@example.com', 'name' => 'Divya Ramesh']);
        $product = Product::factory()->create(['stock_on_hand' => 20]);

        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 1]]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'divya@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertOk()->assertJsonPath('data.customer.email', 'divya@example.com');
    }

    public function test_an_edit_queues_a_fresh_confirmation(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 1]]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertOk();

        Queue::assertPushed(SendOrderConfirmation::class, 2);
    }

    public function test_an_edit_still_refuses_cash_that_no_longer_covers_the_bill(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 100, 'stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 1]]);

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'amount_tendered' => 200,
        ])->assertStatus(422)->assertJsonValidationErrors('amount_tendered');

        $this->assertSame(19, $product->refresh()->stock_on_hand);
    }
}
