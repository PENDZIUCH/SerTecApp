<?php

use App\Mail\ParteCompletadoMail;
use App\Models\Customer;
use App\Models\LookupValue;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\SeedEmailNotificationRecipientsSeeder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

// Cubre la regla nueva de 2026-09-18: a quien le llega el email cuando un
// tecnico completa un parte (cliente/supervisor/tecnico) pasa a ser
// configurable via LookupValue (category='email_notification_recipients'),
// mismo patron que customer_type (ver LookupValueTest). El pedido original
// de Hugo: el cliente siempre recibia el mail, el supervisor solo veia una
// alerta dentro del panel y el tecnico no recibia nada - ahora los 3 son
// toggles on/off, arrancando los 3 activos (backup), administrables desde
// Filament sin tocar codigo.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrador');
    $this->actingAs($this->admin, 'sanctum');
});

function activarDestinatarios(array $activos): void
{
    $todos = ['cliente', 'supervisor', 'tecnico'];
    foreach ($todos as $valor) {
        LookupValue::updateOrCreate(
            ['category' => 'email_notification_recipients', 'value' => $valor],
            ['label' => ucfirst($valor), 'is_active' => in_array($valor, $activos, true)]
        );
    }
}

function payloadParte(WorkOrder $order, User $tecnico): array
{
    return [
        'orden_id' => $order->id,
        'tecnico_id' => $tecnico->id,
        'diagnostico' => 'Diagnóstico de prueba',
        'trabajo_realizado' => 'Se reemplazó el filtro',
        'firma_base64' => str_repeat('a', 120),
    ];
}

test('el seeder de destinatarios de email es idempotente', function () {
    (new SeedEmailNotificationRecipientsSeeder())->run();
    $totalAntes = LookupValue::forCategory('email_notification_recipients')->count();
    expect($totalAntes)->toBe(3);

    // Un admin desactiva "tecnico" a mano.
    LookupValue::forCategory('email_notification_recipients')->where('value', 'tecnico')->update(['is_active' => false]);

    // Correr el seeder de nuevo no debe reactivarlo ni duplicar filas.
    (new SeedEmailNotificationRecipientsSeeder())->run();

    expect(LookupValue::forCategory('email_notification_recipients')->count())->toBe($totalAntes);
    expect(LookupValue::forCategory('email_notification_recipients')->where('value', 'tecnico')->first()->is_active)->toBeFalse();
});

test('con los 3 destinatarios activos se mandan 3 emails al completar un parte', function () {
    Mail::fake();
    activarDestinatarios(['cliente', 'supervisor', 'tecnico']);

    $customer = Customer::factory()->create(['email' => 'cliente@example.com']);
    $tecnico = User::factory()->create(['email' => 'tecnico@example.com']);
    $supervisor = User::factory()->create(['email' => 'supervisor@example.com']);
    $supervisor->assignRole('supervisor');

    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $tecnico->id]);

    $response = $this->postJson('/api/v1/partes', payloadParte($order, $tecnico));

    $response->assertStatus(201);
    Mail::assertSent(ParteCompletadoMail::class, 3);
    Mail::assertSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('cliente@example.com'));
    Mail::assertSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('supervisor@example.com'));
    Mail::assertSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('tecnico@example.com'));
});

test('desactivar un destinatario hace que no se le mande el email', function () {
    Mail::fake();
    activarDestinatarios(['cliente', 'supervisor']); // tecnico desactivado

    $customer = Customer::factory()->create(['email' => 'cliente2@example.com']);
    $tecnico = User::factory()->create(['email' => 'tecnico2@example.com']);
    $supervisor = User::factory()->create(['email' => 'supervisor2@example.com']);
    $supervisor->assignRole('supervisor');

    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $tecnico->id]);

    $response = $this->postJson('/api/v1/partes', payloadParte($order, $tecnico));

    $response->assertStatus(201);
    Mail::assertSent(ParteCompletadoMail::class, 2);
    Mail::assertSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('cliente2@example.com'));
    Mail::assertSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('supervisor2@example.com'));
    Mail::assertNotSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('tecnico2@example.com'));
});

test('si falta el email del cliente o no hay ningun supervisor dado de alta no rompe el guardado del parte', function () {
    Mail::fake();
    activarDestinatarios(['cliente', 'supervisor', 'tecnico']);

    // Cliente sin email (campo nullable en customers) y ningun usuario con
    // rol supervisor/super_admin dado de alta todavia - ambos son los casos
    // "raros" que el toggle de supervisor/cliente no deberia romper.
    $customer = Customer::factory()->create(['email' => null]);
    $tecnico = User::factory()->create(['email' => 'tecnico3@example.com']);

    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $tecnico->id]);

    $response = $this->postJson('/api/v1/partes', payloadParte($order, $tecnico));

    $response->assertStatus(201);
    // Solo el tecnico tiene email valido -> solo el deberia recibir el mail.
    Mail::assertSent(ParteCompletadoMail::class, 1);
    Mail::assertSent(ParteCompletadoMail::class, fn ($mail) => $mail->hasTo('tecnico3@example.com'));
});

test('si los 3 destinatarios estan desactivados no se manda ningun email', function () {
    Mail::fake();
    activarDestinatarios([]);

    $customer = Customer::factory()->create(['email' => 'cliente4@example.com']);
    $tecnico = User::factory()->create(['email' => 'tecnico4@example.com']);

    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $tecnico->id]);

    $response = $this->postJson('/api/v1/partes', payloadParte($order, $tecnico));

    $response->assertStatus(201);
    Mail::assertNothingSent();
});
