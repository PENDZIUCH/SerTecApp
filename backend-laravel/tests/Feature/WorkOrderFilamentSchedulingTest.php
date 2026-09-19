<?php

use App\Filament\Resources\WorkOrderResource\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// Campos scheduled_date/scheduled_time/estimated_duration_minutes en el
// formulario de Filament (2026-09-19): antes solo el "Nueva Orden" de la
// PWA admin podia agendar una orden al crearla (via API ->
// WorkOrderService::create() -> syncBooking()). Filament no tenia estos
// campos, asi que una orden creada ahi nunca generaba Visita sola - Hugo
// pidio paridad entre los dos lados, reusando la misma logica
// (WorkOrderService::syncBooking, ahora publica) en vez de duplicarla.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrador');

    $this->tecnico = User::factory()->create();
    $this->tecnico->assignRole('técnico');

    $this->customer = Customer::factory()->create();
    $this->equipment = Equipment::factory()->create(['customer_id' => $this->customer->id]);
});

test('crear una orden en Filament con tecnico y fecha agenda sola una Visita', function () {
    Mail::fake();
    $this->actingAs($this->admin);

    Livewire::test(CreateWorkOrder::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'assigned_tech_id' => $this->tecnico->id,
            'priority' => 'medium',
            'description' => 'Reparación de prueba',
            'status' => 'pending',
            'scheduled_date' => '2026-10-05',
            'scheduled_time' => '10:30',
            'estimated_duration_minutes' => 45,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = WorkOrder::where('description', 'Reparación de prueba')->firstOrFail();

    $booking = Booking::where('subject_type', WorkOrder::class)->where('subject_id', $order->id)->first();
    expect($booking)->not->toBeNull();
    expect($booking->resource_id)->toBe($this->tecnico->id);
    expect($booking->starts_at->format('Y-m-d H:i'))->toBe('2026-10-05 10:30');
    expect($booking->ends_at->format('Y-m-d H:i'))->toBe('2026-10-05 11:15');
});

test('crear una orden en Filament sin fecha no agenda ninguna Visita', function () {
    Mail::fake();
    $this->actingAs($this->admin);

    Livewire::test(CreateWorkOrder::class)
        ->fillForm([
            'customer_id' => $this->customer->id,
            'assigned_tech_id' => $this->tecnico->id,
            'priority' => 'medium',
            'description' => 'Sin fecha',
            'status' => 'pending',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = WorkOrder::where('description', 'Sin fecha')->firstOrFail();
    expect(Booking::where('subject_type', WorkOrder::class)->where('subject_id', $order->id)->exists())->toBeFalse();
});

test('sacarle la fecha a una orden en Filament borra la Visita agendada', function () {
    Mail::fake();
    $order = WorkOrder::factory()->create([
        'customer_id' => $this->customer->id,
        'assigned_tech_id' => $this->tecnico->id,
        'scheduled_date' => '2026-10-05',
        'scheduled_time' => '09:00',
    ]);
    Booking::create([
        'resource_type' => User::class,
        'resource_id' => $this->tecnico->id,
        'subject_type' => WorkOrder::class,
        'subject_id' => $order->id,
        'starts_at' => '2026-10-05 09:00',
        'ends_at' => '2026-10-05 10:00',
        'status' => 'scheduled',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditWorkOrder::class, ['record' => $order->id])
        ->fillForm(['scheduled_date' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Booking::where('subject_type', WorkOrder::class)->where('subject_id', $order->id)->exists())->toBeFalse();
});
