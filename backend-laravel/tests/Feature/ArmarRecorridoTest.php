<?php

use App\Filament\Resources\BookingResource\Pages\ArmarRecorrido;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// Pantalla "Armar Recorrido" (2026-09-18): el supervisor arma el dia
// completo de un tecnico (varias ordenes, distintos horarios) en una sola
// pantalla en vez de crear una Visita a la vez repitiendo tecnico/fecha.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'tecnico', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrador');

    $this->tecnico = User::factory()->create();
    $this->tecnico->assignRole('técnico');

    $customer = Customer::factory()->create();
    $equipment = Equipment::factory()->create();

    $this->ordenA = WorkOrder::factory()->create(['customer_id' => $customer->id, 'equipment_id' => $equipment->id]);
    $this->ordenB = WorkOrder::factory()->create(['customer_id' => $customer->id, 'equipment_id' => $equipment->id]);
    $this->ordenC = WorkOrder::factory()->create(['customer_id' => $customer->id, 'equipment_id' => $equipment->id]);
});

test('armar un recorrido con varias paradas crea una Booking por cada una', function () {
    $this->actingAs($this->admin);

    Livewire::test(ArmarRecorrido::class)
        ->fillForm([
            'resource_id' => $this->tecnico->id,
            'dia' => '2026-10-01',
            'paradas' => [
                (string) Str::uuid() => ['subject_id' => $this->ordenA->id, 'hora' => '09:00', 'duracion_minutos' => 45],
                (string) Str::uuid() => ['subject_id' => $this->ordenB->id, 'hora' => '11:00', 'duracion_minutos' => 60],
                (string) Str::uuid() => ['subject_id' => $this->ordenC->id, 'hora' => '14:30', 'duracion_minutos' => 30],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Booking::where('resource_type', User::class)->where('resource_id', $this->tecnico->id)->count())->toBe(3);

    $primera = Booking::where('subject_id', $this->ordenA->id)->where('subject_type', WorkOrder::class)->first();
    expect($primera)->not->toBeNull();
    expect($primera->starts_at->format('Y-m-d H:i'))->toBe('2026-10-01 09:00');
    expect($primera->ends_at->format('Y-m-d H:i'))->toBe('2026-10-01 09:45');
    expect($primera->status)->toBe('scheduled');
    expect($primera->resource_id)->toBe($this->tecnico->id);
});

test('el recorrido requiere al menos una parada', function () {
    $this->actingAs($this->admin);

    Livewire::test(ArmarRecorrido::class)
        ->fillForm([
            'resource_id' => $this->tecnico->id,
            'dia' => '2026-10-01',
            'paradas' => [],
        ])
        ->call('save')
        ->assertHasFormErrors(['paradas']);

    expect(Booking::count())->toBe(0);
});

// Regresion 2026-09-18: la lista de Agenda quedo agrupada por tecnico
// (Group::make('resource')) sin decirle a Filament como ordenar la
// consulta real - probaba "ORDER BY resource", columna inexistente, y
// tiraba 500 apenas habia una Booking para mostrar (incluido justo
// despues de guardar un recorrido, por el redirect a esta pantalla).
test('la lista de Agenda renderiza sin error con Visitas creadas', function () {
    Booking::create([
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'subject_type' => WorkOrder::class,
        'subject_id' => $this->ordenA->id,
        'starts_at' => now()->addDay(),
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ListBookings::class)
        ->assertSuccessful();
});
