<?php

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Spatie\Permission\Models\Role;

// Permisos dot-notation viejos (work_orders.view, etc.) no existen mas desde
// la unificacion Roles<->Permisos del 2026-09-04 (ver CLAUDE.md). Mismo
// patron que SecurityPoliciesTest: rol real + seeder real de produccion.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->user = User::factory()->create();
    $this->user->assignRole('administrador');
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
