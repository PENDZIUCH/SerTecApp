<?php

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('equipments.view', 'equipments.create', 'equipments.edit');
    $this->actingAs($this->user, 'sanctum');
});

test('user can list equipment', function () {
    Equipment::factory(5)->create();

    $response = $this->getJson('/api/v1/equipments');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'serial_number', 'status'],
            ],
        ]);
});

test('user can create equipment', function () {
    $customer = Customer::factory()->create();
    $brand = EquipmentBrand::factory()->create();
    $model = EquipmentModel::factory()->for($brand)->create();

    $response = $this->postJson('/api/v1/equipments', [
        'customer_id' => $customer->id,
        'brand_id' => $brand->id,
        'model_id' => $model->id,
        'serial_number' => 'SN-12345',
        'status' => 'active',
    ]);

    $response->assertStatus(201);
});

test('user can change equipment status', function () {
    $equipment = Equipment::factory()->create(['status' => 'active']);

    $response = $this->postJson("/api/v1/equipments/{$equipment->id}/change-status", [
        'status' => 'in_workshop',
        'description' => 'Needs repair',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('equipments', [
        'id' => $equipment->id,
        'status' => 'in_workshop',
    ]);
});
