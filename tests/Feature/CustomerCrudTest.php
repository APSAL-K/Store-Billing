<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_customer(): void
    {
        $this->postJson('/api/customers', ['name' => 'Priya Nair', 'email' => '  PRIYA@Example.com '])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Priya Nair')
            ->assertJsonPath('data.email', 'priya@example.com')
            ->assertJsonPath('data.orders_count', 0);

        $this->assertDatabaseHas('customers', ['email' => 'priya@example.com']);
    }

    public function test_it_refuses_a_duplicate_email(): void
    {
        Customer::factory()->create(['email' => 'priya@example.com']);

        $this->postJson('/api/customers', ['name' => 'Someone Else', 'email' => 'priya@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_it_validates_the_shape(): void
    {
        $this->postJson('/api/customers', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);
        $this->postJson('/api/customers', ['name' => 'A', 'email' => 'nonsense'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_it_updates_a_customer_without_tripping_its_own_email(): void
    {
        $customer = Customer::factory()->create(['name' => 'Priya Nair', 'email' => 'priya@example.com']);

        $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Priya Menon',
            'email' => 'priya@example.com',
        ])->assertOk()->assertJsonPath('data.name', 'Priya Menon');

        $this->assertSame('Priya Menon', $customer->refresh()->name);
    }

    public function test_it_refuses_an_update_that_takes_another_customers_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'priya@example.com']);
        Customer::factory()->create(['email' => 'thomas@example.com']);

        $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Priya Nair',
            'email' => 'thomas@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_it_soft_deletes_a_customer_and_leaves_their_bills_readable(): void
    {
        $customer = Customer::factory()->create(['name' => 'Priya Nair']);
        $order = Order::factory()->for($customer)->create();

        $this->deleteJson("/api/customers/{$customer->id}")->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'customer_id' => $customer->id]);

        $this->get("/orders/{$order->id}")->assertOk()->assertSee('Priya Nair');
        $this->assertCount(0, $this->get('/customers')->viewData('customers'));
    }

    public function test_the_lookup_endpoint_ignores_a_deleted_customer(): void
    {
        $customer = Customer::factory()->create(['email' => 'priya@example.com']);

        $this->getJson('/api/customers/lookup?email=priya@example.com')->assertOk();

        $customer->delete();

        $this->getJson('/api/customers/lookup?email=priya@example.com')->assertNotFound();
    }

    public function test_the_index_endpoint_backs_the_counter_dropdown(): void
    {
        Customer::factory()->create(['name' => 'Priya Nair', 'email' => 'priya@example.com']);
        Customer::factory()->create(['name' => 'Thomas Verghese', 'email' => 'thomas@example.com']);

        $this->getJson('/api/customers')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/customers?search=thomas')->assertOk()->assertJsonCount(1, 'data');
    }
}
