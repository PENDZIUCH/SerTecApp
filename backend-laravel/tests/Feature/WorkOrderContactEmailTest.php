<?php

use App\Filament\Resources\WorkOrderResource\Pages\CreateWorkOrder;
use App\Filament\Resources\WorkOrderResource\Pages\EditWorkOrder;
use App\Mail\OrdenCreadaMail;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

// Campo "contact_email" en el formulario de Orden de Trabajo (2026-09-18):
// antes el email de "orden creada" se mandaba a ciegas al que hubiera en la
// ficha del cliente, sin mostrarlo ni poder corregirlo antes de enviar -
// Hugo encontro un caso real donde casi se manda a un email viejo/de
// prueba. Ahora el campo precarga el email del cliente y, si se corrige
// ahi, ese pasa a ser el vigente (el anterior a secondary_email).
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrador');

    $this->tecnico = User::factory()->create();
    $this->tecnico->assignRole('técnico');
});

test('crear una orden manda el aviso al email corregido y actualiza el cliente', function () {
    Mail::fake();
    $customer = Customer::factory()->create(['email' => 'viejo@ejemplo.com', 'secondary_email' => null]);

    $this->actingAs($this->admin);

    Livewire::test(CreateWorkOrder::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'assigned_tech_id' => $this->tecnico->id,
            'priority' => 'medium',
            'description' => 'Reparación de prueba',
            'status' => 'pending',
            'contact_email' => 'corregido@ejemplo.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'email' => 'corregido@ejemplo.com',
        'secondary_email' => 'viejo@ejemplo.com',
    ]);

    Mail::assertSent(OrdenCreadaMail::class, fn ($mail) => $mail->hasTo('corregido@ejemplo.com'));
});

test('crear una orden sin email de contacto no manda aviso y no rompe', function () {
    Mail::fake();
    $customer = Customer::factory()->create(['email' => null]);

    $this->actingAs($this->admin);

    Livewire::test(CreateWorkOrder::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'assigned_tech_id' => $this->tecnico->id,
            'priority' => 'medium',
            'description' => 'Reparación de prueba',
            'status' => 'pending',
            'contact_email' => '',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    Mail::assertNothingSent();
    $this->assertDatabaseHas('customers', ['id' => $customer->id, 'email' => null]);
});

test('editar una orden con email corregido actualiza el cliente sin reenviar el aviso', function () {
    Mail::fake();
    $customer = Customer::factory()->create(['email' => 'original@ejemplo.com', 'secondary_email' => null]);
    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $this->tecnico->id]);

    $this->actingAs($this->admin);

    Livewire::test(EditWorkOrder::class, ['record' => $order->id])
        ->fillForm(['contact_email' => 'actualizado@ejemplo.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'email' => 'actualizado@ejemplo.com',
        'secondary_email' => 'original@ejemplo.com',
    ]);
    Mail::assertNotSent(OrdenCreadaMail::class);
});
