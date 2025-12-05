<?php

use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('customers.view', 'customers.create', 'customers.edit', 'customers.delete');
    $this->actingAs($this->user, 'sanctum');
});

test('user can list customers', function () {
    Customer::factory(5)->create();

    $response = $this->getJson('/api/v1/customers');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'customer_type', 'full_name', 'email'],
            ],
        ]);
});

test('user can create customer', function () {
    $response = $this->postJson('/api/v1/customers', [
        'customer_type' => 'company',
        'business_name' => 'Test Company',
        'email' => 'company@test.com',
        'phone' => '123456789',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'business_name']);
});

test('user can view customer details', function () {
    $customer = Customer::factory()->create();

    $response = $this->getJson("/api/v1/customers/{$customer->id}");

    $response->assertStatus(200)
        ->assertJson(['id' => $customer->id]);
});

test('user can update customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->putJson("/api/v1/customers/{$customer->id}", [
        'business_name' => 'Updated Company',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'business_name' => 'Updated Company',
    ]);
});

test('user can delete customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->deleteJson("/api/v1/customers/{$customer->id}");

    $response->assertStatus(204);
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});
