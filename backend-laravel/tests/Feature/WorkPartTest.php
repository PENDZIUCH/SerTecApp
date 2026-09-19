<?php

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->tecnico = User::factory()->create();
    $this->tecnico->assignRole('técnico');
    $this->actingAs($this->tecnico, 'sanctum');

    $this->order = WorkOrder::factory()->create([
        'assigned_tech_id' => $this->tecnico->id,
        'customer_id' => Customer::factory()->create()->id,
        'equipment_id' => Equipment::factory()->create()->id,
    ]);
});

// Regresion 2026-09-18: latitude/longitude no estaban en $fillable de
// WorkPart, asi que WorkPart::create() los descartaba en silencio - el
// frontend mandaba las coordenadas bien, pero nunca quedaban guardadas
// (ni la PWA ni Filament mostraban el mapa por eso, no por un bug de UI).
test('saveParte guarda latitude y longitude cuando el tecnico manda GPS', function () {
    $response = $this->postJson('/api/v1/partes', [
        'orden_id' => $this->order->id,
        'tecnico_id' => $this->tecnico->id,
        'diagnostico' => 'Diagnóstico de prueba',
        'trabajo_realizado' => 'Trabajo de prueba',
        'firma_base64' => str_repeat('a', 120),
        'lat' => -34.603722,
        'lng' => -58.381592,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('work_parts', [
        'work_order_id' => $this->order->id,
        'latitude' => -34.603722,
        'longitude' => -58.381592,
    ]);
});

test('saveParte no rompe cuando el tecnico no manda GPS', function () {
    $response = $this->postJson('/api/v1/partes', [
        'orden_id' => $this->order->id,
        'tecnico_id' => $this->tecnico->id,
        'diagnostico' => 'Diagnóstico sin GPS',
        'trabajo_realizado' => 'Trabajo sin GPS',
        'firma_base64' => str_repeat('a', 120),
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('work_parts', [
        'work_order_id' => $this->order->id,
        'latitude' => null,
        'longitude' => null,
    ]);
});

// Feature 2026-09-18: el tecnico puede corregir el email del cliente al
// completar el parte (campo "contact_email") - si difiere del que tenia,
// ese pasa a ser el vigente y el anterior queda en secondary_email (ver
// Customer::updateEmailIfChanged y CustomerEmailUpdateTest).
test('saveParte actualiza el email del cliente cuando el tecnico lo corrige', function () {
    $this->order->customer->update(['email' => 'viejo@ejemplo.com', 'secondary_email' => null]);

    $response = $this->postJson('/api/v1/partes', [
        'orden_id' => $this->order->id,
        'tecnico_id' => $this->tecnico->id,
        'diagnostico' => 'Diagnóstico de prueba',
        'trabajo_realizado' => 'Trabajo de prueba',
        'firma_base64' => str_repeat('a', 120),
        'contact_email' => 'corregido@ejemplo.com',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('customers', [
        'id' => $this->order->customer_id,
        'email' => 'corregido@ejemplo.com',
        'secondary_email' => 'viejo@ejemplo.com',
    ]);
});

test('saveParte no toca el email del cliente si no se manda contact_email', function () {
    $this->order->customer->update(['email' => 'sinTocar@ejemplo.com']);

    $response = $this->postJson('/api/v1/partes', [
        'orden_id' => $this->order->id,
        'tecnico_id' => $this->tecnico->id,
        'diagnostico' => 'Diagnóstico de prueba',
        'trabajo_realizado' => 'Trabajo de prueba',
        'firma_base64' => str_repeat('a', 120),
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('customers', [
        'id' => $this->order->customer_id,
        'email' => 'sinTocar@ejemplo.com',
    ]);
});
