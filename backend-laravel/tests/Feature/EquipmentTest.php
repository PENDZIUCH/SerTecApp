<?php

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\User;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Spatie\Permission\Models\Role;

// Permisos dot-notation viejos (equipments.view, etc.) no existen mas desde
// la unificacion Roles<->Permisos del 2026-09-04 (ver CLAUDE.md). Mismo
// patron que SecurityPoliciesTest: rol real + seeder real de produccion.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->user = User::factory()->create();
    $this->user->assignRole('administrador');
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
    // 'brand' explicito: EquipmentModel::brand() no sigue la convencion que
    // for() adivina por default (esperaria equipmentBrand()) - sin esto tira
    // BadMethodCallException. Encontrado 2026-09-16.
    $model = EquipmentModel::factory()->for($brand, 'brand')->create();

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
