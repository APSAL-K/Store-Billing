<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VoidOrderTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(array $items): int
    {
        return $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => $items,
        ])->assertCreated()->json('data.id');
    }

    public function test_voiding_a_bill_puts_every_unit_back_on_the_shelf(): void
    {
        Queue::fake();

        $first = Product::factory()->create(['stock_on_hand' => 20]);
        $second = Product::factory()->create(['stock_on_hand' => 15]);

        $orderId = $this->placeOrder([
            ['product_id' => $first->id, 'quantity' => 6],
            ['product_id' => $second->id, 'quantity' => 4],
        ]);

        $this->assertSame(14, $first->refresh()->stock_on_hand);
        $this->assertSame(11, $second->refresh()->stock_on_hand);

        $this->deleteJson("/api/orders/{$orderId}", ['reason' => 'Customer changed their mind'])
            ->assertNoContent();

        $this->assertSame(20, $first->refresh()->stock_on_hand);
        $this->assertSame(15, $second->refresh()->stock_on_hand);
    }

    public function test_a_voided_bill_is_kept_for_the_audit_trail(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 2]]);

        $this->deleteJson("/api/orders/{$orderId}", ['reason' => 'Wrong items scanned'])->assertNoContent();

        $this->assertSoftDeleted('orders', ['id' => $orderId]);
        $this->assertDatabaseCount('order_items', 1);

        $order = Order::withTrashed()->find($orderId);
        $this->assertTrue($order->isVoided());
        $this->assertSame('Wrong items scanned', $order->void_reason);

        $this->assertDatabaseHas('stock_movements', [
            'order_id' => $orderId,
            'reason' => StockMovement::REASON_VOID,
            'quantity_change' => 2,
            'note' => 'Wrong items scanned',
        ]);
    }

    public function test_a_voided_bill_drops_out_of_the_order_history(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $kept = $this->placeOrder([['product_id' => $product->id, 'quantity' => 1]]);
        $voided = $this->placeOrder([['product_id' => $product->id, 'quantity' => 1]]);

        $this->deleteJson("/api/orders/{$voided}")->assertNoContent();

        $this->getJson('/api/orders?email=walkin@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $kept);

        $this->getJson('/api/orders?email=walkin@example.com&include_voided=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_a_bill_cannot_be_voided_twice(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 3]]);

        $this->deleteJson("/api/orders/{$orderId}")->assertNoContent();
        $this->assertSame(20, $product->refresh()->stock_on_hand);

        $this->deleteJson("/api/orders/{$orderId}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'This bill has already been voided.');

        $this->assertSame(20, $product->refresh()->stock_on_hand, 'Stock must not be credited a second time.');
    }

    public function test_a_voided_bill_cannot_be_edited(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 3]]);

        $this->deleteJson("/api/orders/{$orderId}")->assertNoContent();

        $this->putJson("/api/orders/{$orderId}", [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(409)->assertJsonPath('message', 'A voided bill cannot be edited.');

        $this->assertSame(20, $product->refresh()->stock_on_hand);
    }

    public function test_the_edit_screen_is_not_reachable_for_a_voided_bill(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);
        $orderId = $this->placeOrder([['product_id' => $product->id, 'quantity' => 1]]);

        $this->deleteJson("/api/orders/{$orderId}")->assertNoContent();

        $this->get("/orders/{$orderId}/edit")->assertNotFound();
        $this->get("/orders/{$orderId}")->assertOk()->assertSee('voided', false);
    }
}
