<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Spatie\Permission\Models\Role;

// Motor de agenda/reservas GENERICO (App\Models\Booking). Mismo patron que
// WorkOrderTest/WorkPartTest: roles reales + SyncShieldPermissionsSeeder,
// nada de permisos dot-notation viejos.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrador');

    $this->tecnico = User::factory()->create();
    $this->tecnico->assignRole('técnico');

    $this->otroTecnico = User::factory()->create();
    $this->otroTecnico->assignRole('técnico');

    $this->order = WorkOrder::factory()->create([
        'assigned_tech_id' => $this->tecnico->id,
        'customer_id' => Customer::factory()->create()->id,
        'equipment_id' => Equipment::factory()->create()->id,
    ]);
});

test('un administrador puede crear una reserva generica', function () {
    $this->actingAs($this->admin, 'sanctum');

    $response = $this->postJson('/api/v1/bookings', [
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'subject_type' => WorkOrder::class,
        'subject_id' => $this->order->id,
        'starts_at' => now()->addDay()->toDateTimeString(),
        'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('bookings', [
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'subject_type' => WorkOrder::class,
        'subject_id' => $this->order->id,
        'status' => 'scheduled',
    ]);
});

test('index filtra por resource_type y resource_id', function () {
    $this->actingAs($this->admin, 'sanctum');

    Booking::create([
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'starts_at' => now(),
        'status' => 'scheduled',
    ]);
    Booking::create([
        'resource_type' => User::class,
        'resource_id' => $this->otroTecnico->id,
        'starts_at' => now(),
        'status' => 'scheduled',
    ]);

    $response = $this->getJson('/api/v1/bookings?resource_type=' . urlencode(User::class) . '&resource_id=' . $this->tecnico->id);

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['resource_id'])->toBe($this->tecnico->id);
});

test('index filtra por status', function () {
    $this->actingAs($this->admin, 'sanctum');

    Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);
    Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now(), 'status' => 'completed']);

    $response = $this->getJson('/api/v1/bookings?status=completed');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['status'])->toBe('completed');
});

test('index filtra por rango de fechas', function () {
    $this->actingAs($this->admin, 'sanctum');

    Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now()->subDays(5), 'status' => 'scheduled']);
    $inRange = Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);
    Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now()->addDays(5), 'status' => 'scheduled']);

    $response = $this->getJson('/api/v1/bookings?from=' . now()->subDay()->toDateTimeString() . '&to=' . now()->addDay()->toDateTimeString());

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($inRange->id);
});

test('un tecnico solo ve sus propias reservas', function () {
    $this->actingAs($this->tecnico, 'sanctum');

    $mine = Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);
    Booking::create(['resource_type' => User::class, 'resource_id' => $this->otroTecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);

    $response = $this->getJson('/api/v1/bookings');

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($mine->id);
});

test('check-in guarda latitude, longitude y timestamp', function () {
    $this->actingAs($this->tecnico, 'sanctum');

    $booking = Booking::create(['resource_type' => User::class, 'resource_id' => $this->tecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/check-in", [
        'latitude' => -34.603722,
        'longitude' => -58.381592,
    ]);

    $response->assertStatus(200);
    $booking->refresh();

    expect($booking->status)->toBe('in_progress');
    expect($booking->check_in)->not->toBeNull();
    expect((float) $booking->latitude)->toBe(-34.603722);
    expect((float) $booking->longitude)->toBe(-58.381592);
});

test('check-out calcula la duracion entre check-in y check-out', function () {
    $this->actingAs($this->tecnico, 'sanctum');

    $booking = Booking::create([
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'starts_at' => now(),
        'status' => 'in_progress',
        'check_in' => now()->subMinutes(30),
    ]);

    $response = $this->postJson("/api/v1/bookings/{$booking->id}/check-out");

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'completed');
    expect($response->json('duration_minutes'))->toBeGreaterThanOrEqual(29);

    $booking->refresh();
    expect($booking->status)->toBe('completed');
    expect($booking->check_out)->not->toBeNull();
    expect($booking->calculateDuration())->toBeGreaterThanOrEqual(29);
});

test('un tecnico no puede ver la reserva de otro tecnico', function () {
    $this->actingAs($this->tecnico, 'sanctum');

    $ajena = Booking::create(['resource_type' => User::class, 'resource_id' => $this->otroTecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);

    $response = $this->getJson("/api/v1/bookings/{$ajena->id}");

    $response->assertStatus(403);
});

test('un tecnico no puede hacer check-in de la reserva de otro tecnico', function () {
    $this->actingAs($this->tecnico, 'sanctum');

    $ajena = Booking::create(['resource_type' => User::class, 'resource_id' => $this->otroTecnico->id, 'starts_at' => now(), 'status' => 'scheduled']);

    $response = $this->postJson("/api/v1/bookings/{$ajena->id}/check-in", [
        'latitude' => -34.6,
        'longitude' => -58.4,
    ]);

    $response->assertStatus(403);
});

test('agendar una orden con tecnico y fecha crea un booking generico ligado a la orden', function () {
    $this->actingAs($this->admin, 'sanctum');

    $customer = Customer::factory()->create();

    $response = $this->postJson('/api/v1/work-orders', [
        'customer_id' => $customer->id,
        'title' => 'Reparacion con agenda',
        'assigned_tech_id' => $this->tecnico->id,
        'scheduled_date' => now()->addDays(2)->toDateString(),
        'scheduled_time' => '14:30',
        'estimated_duration_minutes' => 90,
    ]);

    $response->assertStatus(201);
    $workOrderId = $response->json('id');

    $this->assertDatabaseHas('bookings', [
        'subject_type' => WorkOrder::class,
        'subject_id' => $workOrderId,
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
    ]);

    $booking = Booking::where('subject_type', WorkOrder::class)->where('subject_id', $workOrderId)->first();
    expect($booking)->not->toBeNull();
    expect($booking->starts_at->format('H:i'))->toBe('14:30');
    expect((int) $booking->starts_at->diffInMinutes($booking->ends_at))->toBe(90);
});

test('desasignar el tecnico de una orden elimina el booking generado', function () {
    $this->actingAs($this->admin, 'sanctum');

    $customer = Customer::factory()->create();
    $order = WorkOrder::factory()->create([
        'customer_id' => $customer->id,
        'assigned_tech_id' => $this->tecnico->id,
        'scheduled_date' => now()->addDay(),
    ]);

    // Simula el flujo real: crear via API para que el booking exista.
    Booking::create([
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'subject_type' => WorkOrder::class,
        'subject_id' => $order->id,
        'starts_at' => now()->addDay(),
        'status' => 'scheduled',
    ]);

    $response = $this->putJson("/api/v1/work-orders/{$order->id}", [
        'assigned_tech_id' => null,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseMissing('bookings', [
        'subject_type' => WorkOrder::class,
        'subject_id' => $order->id,
    ]);
});
