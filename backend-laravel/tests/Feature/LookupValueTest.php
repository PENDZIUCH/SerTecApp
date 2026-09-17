<?php

use App\Models\Customer;
use App\Models\LookupValue;
use App\Models\User;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

// Cubre la regla nueva de 2026-09-17: los tipos de cliente ("customer_type")
// pasan a ser una lista administrable (LookupValue) en vez de un enum fijo.
// Mismo patron de setup que WorkOrderTest/SecurityPoliciesTest: rol real +
// seeder real de produccion.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->user = User::factory()->create();
    $this->user->assignRole('administrador');
    $this->actingAs($this->user, 'sanctum');
});

test('category y value son unicos juntos', function () {
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'hotel']);

    expect(fn () => LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'hotel']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('optionsFor solo devuelve activos, ordenados', function () {
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'b', 'label' => 'Beta', 'sort_order' => 2]);
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'a', 'label' => 'Alfa', 'sort_order' => 1]);
    LookupValue::factory()->inactive()->create(['category' => 'customer_type', 'value' => 'c', 'label' => 'Gamma', 'sort_order' => 0]);

    $options = LookupValue::optionsFor('customer_type');

    expect($options)->toBe(['a' => 'Alfa', 'b' => 'Beta']);
});

test('crear cliente con un tipo nuevo agregado dinamicamente funciona sin tocar codigo', function () {
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'hotel', 'label' => 'Hotel']);

    $response = $this->postJson('/api/v1/customers', [
        'customer_type' => 'hotel',
        'business_name' => 'Hotel Test',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('customers', ['business_name' => 'Hotel Test', 'customer_type' => 'hotel']);
});

test('crear cliente con un tipo inactivo se rechaza', function () {
    LookupValue::factory()->inactive()->create(['category' => 'customer_type', 'value' => 'viejo', 'label' => 'Viejo']);

    $response = $this->postJson('/api/v1/customers', [
        'customer_type' => 'viejo',
        'business_name' => 'No debería crearse',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('customer_type');
});

test('tipo individual pide nombre y apellido, cualquier otro tipo pide razon social', function () {
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'individual', 'label' => 'Particular']);
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'consorcio', 'label' => 'Consorcio']);

    $sinNombre = $this->postJson('/api/v1/customers', ['customer_type' => 'individual']);
    $sinNombre->assertStatus(422)->assertJsonValidationErrors(['first_name', 'last_name']);

    $sinRazonSocial = $this->postJson('/api/v1/customers', ['customer_type' => 'consorcio']);
    $sinRazonSocial->assertStatus(422)->assertJsonValidationErrors('business_name');
});

test('desactivar un tipo despues no rompe editar un cliente que ya lo tenia', function () {
    $tipo = LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'viejo', 'label' => 'Viejo']);
    $customer = Customer::factory()->create(['customer_type' => 'viejo', 'business_name' => 'Cliente Viejo']);

    $tipo->update(['is_active' => false]);

    // Editar otro campo, sin tocar el tipo - no deberia fallar por el tipo desactivado.
    $response = $this->putJson("/api/v1/customers/{$customer->id}", [
        'business_name' => 'Cliente Viejo Actualizado',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'business_name' => 'Cliente Viejo Actualizado', 'customer_type' => 'viejo']);
});

test('endpoint publico de lookup-values requiere auth y solo devuelve activos', function () {
    LookupValue::factory()->create(['category' => 'customer_type', 'value' => 'hotel', 'label' => 'Hotel', 'sort_order' => 1]);
    LookupValue::factory()->inactive()->create(['category' => 'customer_type', 'value' => 'viejo', 'label' => 'Viejo']);

    // Auth::forgetGuards() para simular un request sin token real, ya que
    // el beforeEach ya dejo el guard 'sanctum' autenticado (mismo truco
    // documentado en MagicLinkSingleUseTest para requests consecutivos).
    \Illuminate\Support\Facades\Auth::forgetGuards();
    $sinToken = $this->getJson('/api/v1/lookup-values/customer_type');
    $sinToken->assertStatus(401);

    $this->actingAs($this->user, 'sanctum');
    $conToken = $this->getJson('/api/v1/lookup-values/customer_type');
    $conToken->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['value' => 'hotel', 'label' => 'Hotel']);
});

test('la migracion de seed de tipos de cliente es idempotente', function () {
    (new \Database\Seeders\SeedCustomerTypesSeeder())->run();
    $totalAntes = LookupValue::forCategory('customer_type')->count();

    // Un admin desactiva "gym" a mano.
    LookupValue::forCategory('customer_type')->where('value', 'gym')->update(['is_active' => false]);

    // Correr el seeder de nuevo no debe reactivarlo ni duplicar filas.
    (new \Database\Seeders\SeedCustomerTypesSeeder())->run();

    expect(LookupValue::forCategory('customer_type')->count())->toBe($totalAntes);
    expect(LookupValue::forCategory('customer_type')->where('value', 'gym')->first()->is_active)->toBeFalse();
});
