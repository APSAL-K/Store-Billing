<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The screens are thin, but they are what a reviewer opens first, so the pages
 * are checked for the data they are supposed to be showing.
 */
class CounterScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_counter_screen_lists_products_that_are_low_on_stock(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        Product::factory()->withStock(3)->create(['name' => 'Brown Bread 400g']);
        Product::factory()->withStock(80)->create(['name' => 'Basmati Rice 5kg']);

        $response = $this->get('/')->assertOk();

        $response->assertSee('Brown Bread 400g');
        $this->assertCount(1, $response->viewData('lowStock'));
        $this->assertCount(2, $response->viewData('catalogue'), 'The picker needs the whole catalogue.');
    }

    public function test_the_order_list_shows_the_newest_bill_first(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->for($customer)->create(['placed_at' => now()->subWeek()]);
        $newest = Order::factory()->for($customer)->create(['placed_at' => now()]);

        $response = $this->get('/orders')->assertOk();

        $this->assertSame($newest->id, $response->viewData('orders')->first()->id);
    }

    public function test_the_order_list_can_be_filtered_by_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->for($customer)->create();
        Order::factory()->count(2)->create();

        $response = $this->get('/orders?email=divya@example.com')->assertOk();

        $this->assertCount(1, $response->viewData('orders'));
    }

    public function test_a_bill_can_be_opened(): void
    {
        $order = Order::factory()->create();

        $this->get("/orders/{$order->id}")
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($order->customer->email);
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
}
