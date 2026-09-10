<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\PerPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_listing_paginates_at_the_default_size(): void
    {
        Order::factory()->count(14)->create();
        Customer::factory()->count(14)->create();
        Product::factory()->count(14)->create();

        foreach (['/orders', '/customers', '/products'] as $path) {
            $paginator = $this->get($path)->assertOk()->viewData(
                $path === '/orders' ? 'orders' : ltrim($path, '/')
            );

            $this->assertSame(10, $paginator->perPage(), "{$path} should default to ten a page.");
            $this->assertTrue($paginator->hasPages(), "{$path} should be paginated.");
            $this->assertCount(10, $paginator);
        }
    }

    public function test_a_second_page_carries_the_rest(): void
    {
        Order::factory()->count(14)->create();

        $second = $this->get('/orders?page=2')->assertOk()->viewData('orders');

        $this->assertSame(2, $second->currentPage());
        $this->assertCount(4, $second);
    }

    public function test_the_page_size_can_be_changed(): void
    {
        Product::factory()->count(30)->create();

        $this->assertCount(25, $this->get('/products?per_page=25')->viewData('products'));
        $this->assertCount(30, $this->get('/products?per_page=50')->viewData('products'));
    }

    public function test_an_unsupported_page_size_falls_back_to_the_default(): void
    {
        Product::factory()->count(30)->create();

        foreach (['9999', '0', '-5', 'lots', ''] as $requested) {
            $this->assertSame(
                10,
                $this->get("/products?per_page={$requested}")->viewData('products')->perPage(),
                "per_page={$requested} should fall back to the default."
            );
        }
    }

    public function test_filters_survive_a_page_change(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->count(14)->for($customer)->create();
        Order::factory()->count(5)->create();

        $page = $this->get('/orders?email=divya@example.com&per_page=10&page=2')->assertOk()->viewData('orders');

        $this->assertSame(14, $page->total(), 'The email filter must still apply on page two.');
        $this->assertStringContainsString('email=divya%40example.com', $page->previousPageUrl());
        $this->assertStringContainsString('per_page=10', $page->previousPageUrl());
    }

    public function test_the_nested_listings_paginate_too(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->count(12)->for($customer)->create();

        $product = Product::factory()->create();
        StockMovement::factory()->count(12)->for($product)->create();

        $this->assertCount(10, $this->get("/customers/{$customer->id}")->assertOk()->viewData('orders'));
        $this->assertCount(10, $this->get("/products/{$product->id}")->assertOk()->viewData('movements'));
    }

    public function test_the_page_size_options_are_what_the_selector_offers(): void
    {
        $this->assertSame([10, 25, 50, 100], PerPage::OPTIONS);
    }
}
