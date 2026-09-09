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

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_order_and_returns_the_priced_lines(): void
    {
        Queue::fake();

        $soap = Product::factory()->create([
            'name' => 'Dove Soap 100g',
            'unit_price' => 62.00,
            'tax_percentage' => 18,
            'stock_on_hand' => 20,
        ]);

        $tea = Product::factory()->create([
            'unit_price' => 320.00,
            'tax_percentage' => 5,
            'stock_on_hand' => 20,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [
                ['product_id' => $soap->id, 'quantity' => 3],
                ['product_id' => $tea->id, 'quantity' => 1],
            ],
        ]);

        // 3 x 62.00 = 186.00 + 18% tax (33.48); 1 x 320.00 = 320.00 + 5% tax (16.00)
        $response->assertCreated()
            ->assertJsonPath('data.totals.subtotal', '506.00')
            ->assertJsonPath('data.totals.tax', '49.48')
            ->assertJsonPath('data.totals.grand_total', '555.48')
            ->assertJsonPath('data.customer.email', 'walkin@example.com')
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseHas('order_items', [
            'product_id' => $soap->id,
            'quantity' => 3,
            'unit_price' => '62.00',
            'line_subtotal' => '186.00',
            'line_tax' => '33.48',
            'line_total' => '219.48',
        ]);
    }

    public function test_it_deducts_stock_and_records_a_stock_movement(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 12]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ])->assertCreated();

        $this->assertSame(8, $product->refresh()->stock_on_hand);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'reason' => StockMovement::REASON_SALE,
            'quantity_change' => -4,
            'balance_after' => 8,
        ]);
    }

    public function test_it_rejects_an_order_when_stock_is_short_and_changes_nothing(): void
    {
        Queue::fake();

        $inStock = Product::factory()->create(['stock_on_hand' => 50]);
        $almostOut = Product::factory()->create(['name' => 'Farm Eggs (12)', 'stock_on_hand' => 2]);

        $response = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [
                ['product_id' => $inStock->id, 'quantity' => 1],
                ['product_id' => $almostOut->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('shortages.0.product_id', $almostOut->id)
            ->assertJsonPath('shortages.0.requested', 5)
            ->assertJsonPath('shortages.0.available', 2);

        // The whole order is rejected, so the line that could have been filled
        // must not have been taken out of stock either.
        $this->assertSame(50, $inStock->refresh()->stock_on_hand);
        $this->assertSame(2, $almostOut->refresh()->stock_on_hand);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stock_movements', 0);

        Queue::assertNothingPushed();
    }

    public function test_it_refuses_to_sell_a_product_that_is_out_of_stock(): void
    {
        Queue::fake();

        $product = Product::factory()->outOfStock()->create();

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonPath('shortages.0.available', 0);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_it_reuses_an_existing_customer_matched_on_email(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => 'thomas@example.com', 'name' => 'Thomas Verghese']);
        $product = Product::factory()->create(['stock_on_hand' => 10]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'THOMAS@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated()->assertJsonPath('data.customer.id', $customer->id);

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame('Thomas Verghese', $customer->refresh()->name);
    }

    public function test_it_requires_a_name_the_first_time_an_email_is_seen(): void
    {
        $product = Product::factory()->create(['stock_on_hand' => 10]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'stranger@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('customer.name');
    }

    public function test_it_merges_repeated_lines_for_the_same_product(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 10, 'unit_price' => 50.00])->fresh();
        $product->update(['tax_percentage' => 0]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ])->assertCreated()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.quantity', 5)
            ->assertJsonPath('data.totals.grand_total', '250.00');

        $this->assertSame(5, $product->refresh()->stock_on_hand);
    }

    public function test_it_returns_the_change_due_and_its_denominations(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 277.50, 'stock_on_hand' => 10]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_tendered' => 1000,
        ])->assertCreated()
            ->assertJsonPath('data.payment.change_due', '445.00')
            ->assertJsonPath('data.payment.change_breakdown', [
                ['denomination' => 200, 'count' => 2],
                ['denomination' => 20, 'count' => 2],
                ['denomination' => 5, 'count' => 1],
            ]);
    }

    public function test_it_rejects_cash_that_does_not_cover_the_bill(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 100.00, 'stock_on_hand' => 10]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_tendered' => 150,
        ])->assertStatus(422)->assertJsonValidationErrors('amount_tendered');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->refresh()->stock_on_hand);
    }

    public function test_it_validates_the_shape_of_the_request(): void
    {
        $this->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer.email', 'items']);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'not-an-email', 'name' => 'Nobody'],
            'items' => [['product_id' => 9999, 'quantity' => 0]],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'customer.email',
            'items.0.product_id',
            'items.0.quantity',
        ]);
    }

    public function test_it_queues_the_confirmation_email_after_the_order_commits(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 10]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $order = Order::sole();

        Queue::assertPushed(
            SendOrderConfirmation::class,
            fn (SendOrderConfirmation $job): bool => $job->orderId === $order->id
        );
    }
}
