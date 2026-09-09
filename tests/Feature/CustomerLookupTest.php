<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_known_customer_with_their_order_count(): void
    {
        $customer = Customer::factory()->create(['email' => 'thomas@example.com', 'name' => 'Thomas Verghese']);
        Order::factory()->count(3)->for($customer)->create();

        $this->getJson('/api/customers/lookup?email=thomas@example.com')
            ->assertOk()
            ->assertJsonPath('data.name', 'Thomas Verghese')
            ->assertJsonPath('data.orders_count', 3);
    }

    public function test_it_matches_regardless_of_case_and_surrounding_space(): void
    {
        Customer::factory()->create(['email' => 'thomas@example.com']);

        $this->getJson('/api/customers/lookup?email='.urlencode('  THOMAS@Example.com  '))
            ->assertOk()
            ->assertJsonPath('data.email', 'thomas@example.com');
    }

    /**
     * The counter screen treats a 404 as "new customer" and asks for a name, so
     * this has to stay a clean not-found rather than an error.
     */
    public function test_it_returns_not_found_for_an_unknown_email(): void
    {
        $this->getJson('/api/customers/lookup?email=stranger@example.com')->assertNotFound();
    }

    public function test_it_requires_a_valid_email(): void
    {
        $this->getJson('/api/customers/lookup')->assertStatus(422)->assertJsonValidationErrors('email');
        $this->getJson('/api/customers/lookup?email=nonsense')->assertStatus(422)->assertJsonValidationErrors('email');
    }
}
