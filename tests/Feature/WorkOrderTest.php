<?php

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('work_orders.view', 'work_orders.create', 'work_orders.edit');
    $this->actingAs($this->user, 'sanctum');
});

test('user can list work orders', function () {
    WorkOrder::factory(5)->create();

    $response = $this->getJson('/api/v1/work-orders');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'wo_number', 'title', 'status'],
            ],
        ]);
});

test('user can create work order', function () {
    $customer = Customer::factory()->create();
    $equipment = Equipment::factory()->create();

    $response = $this->postJson('/api/v1/work-orders', [
        'customer_id' => $customer->id,
        'equipment_id' => $equipment->id,
        'title' => 'Test Work Order',
        'description' => 'Test description',
        'priority' => 'medium',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'wo_number']);
});

test('user can change work order status', function () {
    $workOrder = WorkOrder::factory()->create(['status' => 'pending']);

    $response = $this->postJson("/api/v1/work-orders/{$workOrder->id}/change-status", [
        'status' => 'in_progress',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('work_orders', [
        'id' => $workOrder->id,
        'status' => 'in_progress',
    ]);
});
