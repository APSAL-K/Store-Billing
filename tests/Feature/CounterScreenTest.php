<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CounterScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_reports_todays_trade(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 100, 'stock_on_hand' => 50]);

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertCreated();

        Order::factory()->create(['placed_at' => now()->subMonth(), 'grand_total' => 900]);

        $response = $this->get('/')->assertOk();

        $this->assertSame(1, $response->viewData('today')['orders']);
        $this->assertSame(300.0, $response->viewData('today')['revenue']);
        $this->assertSame(3, $response->viewData('today')['units']);
        $this->assertCount(14, $response->viewData('trend'));
    }

    public function test_the_dashboard_leaves_voided_bills_out_of_revenue(): void
    {
        Queue::fake();

        $product = Product::factory()->taxFree()->create(['unit_price' => 100, 'stock_on_hand' => 50]);

        $orderId = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->json('data.id');

        $this->deleteJson("/api/orders/{$orderId}")->assertNoContent();

        $response = $this->get('/')->assertOk();

        $this->assertSame(0, $response->viewData('today')['orders']);
        $this->assertSame(0.0, $response->viewData('today')['revenue']);
        $this->assertSame(1, $response->viewData('allTime')['deleted']);
    }

    public function test_the_counter_screen_lists_products_that_are_low_on_stock(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        Product::factory()->withStock(3)->create(['name' => 'Brown Bread 400g']);
        Product::factory()->withStock(80)->create(['name' => 'Basmati Rice 5kg']);

        $response = $this->get('/pos')->assertOk();

        $response->assertSee('Brown Bread 400g');
        $this->assertCount(1, $response->viewData('lowStock'));
        $this->assertCount(2, $response->viewData('catalogue'), 'The picker needs the whole catalogue.');
    }

    public function test_the_edit_screen_adds_the_bills_own_units_back_to_sellable_stock(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 10]);

        $orderId = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ])->json('data.id');

        $this->assertSame(6, $product->refresh()->stock_on_hand);

        $response = $this->get("/orders/{$orderId}/edit")->assertOk();

        $catalogue = collect($response->viewData('catalogue'))->firstWhere('id', $product->id);

        $this->assertSame(10, $catalogue['stock_on_hand'], 'The four units on this bill are still sellable on it.');
        $this->assertCount(1, $response->viewData('existingLines'));
    }

    public function test_the_order_list_shows_the_newest_bill_first(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->for($customer)->create(['placed_at' => now()->subWeek()]);
        $newest = Order::factory()->for($customer)->create(['placed_at' => now()]);

        $response = $this->get('/orders')->assertOk();

        $this->assertSame($newest->id, $response->viewData('orders')->first()->id);
    }

    public function test_the_order_list_can_be_filtered_by_email_and_by_voided(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->for($customer)->create();
        Order::factory()->count(2)->create();

        $product = Product::factory()->create(['stock_on_hand' => 10]);
        $orderId = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->json('data.id');
        $this->deleteJson("/api/orders/{$orderId}")->assertNoContent();

        $this->assertCount(1, $this->get('/orders?email=divya@example.com')->viewData('orders'));
        $this->assertCount(3, $this->get('/orders')->viewData('orders'), 'The voided bill is hidden by default.');
        $this->assertCount(1, $this->get('/orders?deleted=1')->viewData('orders'));
    }

    public function test_a_bill_can_be_opened(): void
    {
        $order = Order::factory()->create();

        $this->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($order->customer->email);
    }

    public function test_the_customer_list_ranks_by_lifetime_value(): void
    {
        $big = Customer::factory()->create(['name' => 'Big Spender']);
        $small = Customer::factory()->create(['name' => 'Small Spender']);

        Order::factory()->for($big)->create(['grand_total' => 5000]);
        Order::factory()->for($small)->create(['grand_total' => 100]);

        $customers = $this->get('/customers')->assertOk()->viewData('customers');

        $this->assertSame($big->id, $customers->first()->id);
        $this->assertSame(5000.0, (float) $customers->first()->lifetime_value);
    }

    public function test_a_customer_page_shows_their_bills_including_voided_ones(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 20]);

        $kept = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->json('data.id');

        $voided = $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->json('data.id');

        $this->deleteJson("/api/orders/{$voided}")->assertNoContent();

        $customer = Customer::where('email', 'walkin@example.com')->sole();
        $response = $this->get("/customers/{$customer->id}")->assertOk();

        $this->assertCount(2, $response->viewData('orders'));
        $this->assertSame(1, $response->viewData('customer')->orders_count);
        $this->assertSame($kept, $response->viewData('orders')->last()->id);
    }

    public function test_the_inventory_screen_summarises_stock(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        Product::factory()->outOfStock()->create();
        Product::factory()->withStock(5)->create();
        Product::factory()->withStock(100)->create();

        $stats = $this->get('/products')->assertOk()->viewData('stats');

        $this->assertSame(3, $stats['total']);
        $this->assertSame(2, $stats['low'], 'Out of stock counts as low as well.');
        $this->assertSame(1, $stats['out']);
        $this->assertSame(105, $stats['units']);
    }

    public function test_the_inventory_screen_can_be_filtered(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        Product::factory()->withStock(4)->create(['name' => 'Brown Bread 400g', 'code' => 'GRO-1002']);
        Product::factory()->withStock(90)->create(['name' => 'Basmati Rice 5kg', 'code' => 'GRO-1004']);

        $this->assertCount(1, $this->get('/products?search=bread')->viewData('products'));
        $this->assertCount(1, $this->get('/products?search=GRO-1004')->viewData('products'));
        $this->assertCount(1, $this->get('/products?low_stock=1')->viewData('products'));
        $this->assertCount(2, $this->get('/products')->viewData('products'));
    }

    public function test_a_product_page_shows_its_stock_ledger(): void
    {
        Queue::fake();

        $product = Product::factory()->withStock(20)->create();

        $this->postJson('/api/orders', [
            'customer' => ['email' => 'walkin@example.com', 'name' => 'Walk-in Customer'],
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertCreated();

        $this->postJson("/api/products/{$product->id}/restock", ['quantity' => 10])->assertOk();

        $response = $this->get("/products/{$product->id}")->assertOk();

        $this->assertCount(2, $response->viewData('movements'));
        $this->assertSame(3, $response->viewData('sold'));
    }
}
