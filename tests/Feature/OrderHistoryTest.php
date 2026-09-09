<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_customers_orders_newest_first(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);

        $oldest = Order::factory()->for($customer)->create(['placed_at' => now()->subDays(10)]);
        $newest = Order::factory()->for($customer)->create(['placed_at' => now()->subHour()]);
        $middle = Order::factory()->for($customer)->create(['placed_at' => now()->subDays(3)]);

        $this->getJson('/api/orders?email=divya@example.com')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonPath('data.1.id', $middle->id)
            ->assertJsonPath('data.2.id', $oldest->id);
    }

    public function test_it_only_returns_orders_belonging_to_that_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->for($customer)->create();
        Order::factory()->count(3)->create();

        $this->getJson('/api/orders?email=divya@example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_it_matches_the_email_regardless_of_case(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->for($customer)->create();

        $this->getJson('/api/orders?email=DIVYA@Example.com')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_paginates(): void
    {
        $customer = Customer::factory()->create(['email' => 'divya@example.com']);
        Order::factory()->count(5)->for($customer)->create();

        $this->getJson('/api/orders?email=divya@example.com&per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_it_returns_not_found_for_an_unknown_email(): void
    {
        $this->getJson('/api/orders?email=nobody@example.com')->assertNotFound();
    }

    public function test_it_requires_an_email(): void
    {
        $this->getJson('/api/orders')->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
