<?php

use App\Models\Customer;
use App\Models\LookupValue;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\PushNotification;
use App\Services\PushNotificationDispatcher;
use Database\Seeders\SeedPushNotificationEventsSeeder;
use Database\Seeders\SyncShieldPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

// Cubre la feature de Web Push (2026-09-18): 4 eventos configurables via
// LookupValue (category='push_notification_events'), mismo patron que
// 'email_notification_recipients' (ver EmailNotificationRecipientsTest).
// Notification::fake() en vez de mandar push real - lo que se prueba es
// que el toggle en lookup_values efectivamente controla si se llama a
// $user->notify(new PushNotification(...)), no el transporte real
// (Minishlink\WebPush), que no corre en tests.
// A diferencia de EmailNotificationRecipientsTest, NO se hace actingAs()
// global aca: el test de "requiere autenticacion" del endpoint de
// suscripcion necesita una request realmente anonima, y actingAs() en
// beforeEach dejaria el guard 'sanctum' con usuario puesto para todos los
// tests (mismo criterio que PublicEndpointsClosedTest). Cada test que
// necesita estar autenticado llama a $this->actingAs(...) el mismo.
beforeEach(function () {
    Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
    (new SyncShieldPermissionsSeeder())->run();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrador');
});

function activarEventosPush(array $activos): void
{
    $todos = ['parte_rechazado', 'parte_aprobado', 'parte_pendiente_aprobacion', 'orden_nueva_asignada'];
    foreach ($todos as $valor) {
        LookupValue::updateOrCreate(
            ['category' => 'push_notification_events', 'value' => $valor],
            ['label' => ucfirst($valor), 'is_active' => in_array($valor, $activos, true)]
        );
    }
}

// --- Seeder ---------------------------------------------------------------

test('el seeder de eventos de push es idempotente y no pisa toggles apagados a mano', function () {
    (new SeedPushNotificationEventsSeeder())->run();
    $totalAntes = LookupValue::forCategory('push_notification_events')->count();
    expect($totalAntes)->toBe(4);

    LookupValue::forCategory('push_notification_events')->where('value', 'orden_nueva_asignada')->update(['is_active' => false]);

    (new SeedPushNotificationEventsSeeder())->run();

    expect(LookupValue::forCategory('push_notification_events')->count())->toBe($totalAntes);
    expect(LookupValue::forCategory('push_notification_events')->where('value', 'orden_nueva_asignada')->first()->is_active)->toBeFalse();
});

// --- PushNotificationDispatcher (usado por los 4 call sites) --------------

test('el dispatcher manda el push si el evento esta activo', function () {
    Notification::fake();
    activarEventosPush(['parte_aprobado']);

    $tecnico = User::factory()->create();

    PushNotificationDispatcher::send('parte_aprobado', $tecnico, 'Título', 'Cuerpo');

    Notification::assertSentTo($tecnico, PushNotification::class);
});

test('el dispatcher NO manda el push si el evento esta desactivado', function () {
    Notification::fake();
    activarEventosPush([]); // todo apagado

    $tecnico = User::factory()->create();

    PushNotificationDispatcher::send('parte_aprobado', $tecnico, 'Título', 'Cuerpo');

    Notification::assertNothingSentTo($tecnico);
});

test('el dispatcher no manda nada si el evento ni siquiera existe en lookup_values', function () {
    Notification::fake();
    // Sin seedear la categoria: optionsFor() devuelve [] -> array_key_exists siempre falso.

    $tecnico = User::factory()->create();

    PushNotificationDispatcher::send('parte_aprobado', $tecnico, 'Título', 'Cuerpo');

    Notification::assertNothingSentTo($tecnico);
});

test('el dispatcher acepta una lista de destinatarios (supervisores)', function () {
    Notification::fake();
    activarEventosPush(['parte_pendiente_aprobacion']);

    $supervisorA = User::factory()->create();
    $supervisorB = User::factory()->create();

    PushNotificationDispatcher::send('parte_pendiente_aprobacion', collect([$supervisorA, $supervisorB]), 'Título', 'Cuerpo');

    Notification::assertSentTo($supervisorA, PushNotification::class);
    Notification::assertSentTo($supervisorB, PushNotification::class);
});

// --- parte_pendiente_aprobacion (TechnicianController::saveParte) ---------

test('completar un parte manda push a los supervisores si el evento esta activo', function () {
    Notification::fake();
    activarEventosPush(['parte_pendiente_aprobacion']);

    $customer = Customer::factory()->create();
    $tecnico = User::factory()->create();
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $tecnico->id]);

    $this->actingAs($tecnico, 'sanctum');
    $response = $this->postJson('/api/v1/partes', [
        'orden_id' => $order->id,
        'tecnico_id' => $tecnico->id,
        'diagnostico' => 'Diagnóstico de prueba',
        'trabajo_realizado' => 'Se reemplazó el filtro',
        'firma_base64' => str_repeat('a', 120),
    ]);

    $response->assertStatus(201);
    Notification::assertSentTo($supervisor, PushNotification::class);
});

test('completar un parte NO manda push si el evento parte_pendiente_aprobacion esta desactivado', function () {
    Notification::fake();
    activarEventosPush([]); // todo apagado

    $customer = Customer::factory()->create();
    $tecnico = User::factory()->create();
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    $order = WorkOrder::factory()->create(['customer_id' => $customer->id, 'assigned_tech_id' => $tecnico->id]);

    $this->actingAs($tecnico, 'sanctum');
    $response = $this->postJson('/api/v1/partes', [
        'orden_id' => $order->id,
        'tecnico_id' => $tecnico->id,
        'diagnostico' => 'Diagnóstico de prueba',
        'trabajo_realizado' => 'Se reemplazó el filtro',
        'firma_base64' => str_repeat('a', 120),
    ]);

    $response->assertStatus(201);
    // La notificacion in-app de Filament (sendToDatabase) sigue mandandose
    // siempre, sin importar el toggle - solo el Web Push se apaga. Por eso
    // se verifica la clase especifica, no "nada en absoluto".
    Notification::assertNotSentTo($supervisor, PushNotification::class);
});

// --- orden_nueva_asignada (WorkOrderService, camino API / admin PWA) ------

test('crear una orden con tecnico asignado manda push si el evento esta activo', function () {
    Notification::fake();
    activarEventosPush(['orden_nueva_asignada']);

    $customer = Customer::factory()->create();
    $tecnico = User::factory()->create();
    $tecnico->assignRole('técnico');

    $this->actingAs($this->admin, 'sanctum');
    $response = $this->postJson('/api/v1/work-orders', [
        'customer_id' => $customer->id,
        'title' => 'Reparar cinta',
        'assigned_tech_id' => $tecnico->id,
    ]);

    $response->assertStatus(201);
    Notification::assertSentTo($tecnico, PushNotification::class);
});

test('crear una orden NO manda push si el evento orden_nueva_asignada esta desactivado', function () {
    Notification::fake();
    activarEventosPush([]);

    $customer = Customer::factory()->create();
    $tecnico = User::factory()->create();
    $tecnico->assignRole('técnico');

    $this->actingAs($this->admin, 'sanctum');
    $response = $this->postJson('/api/v1/work-orders', [
        'customer_id' => $customer->id,
        'title' => 'Reparar cinta',
        'assigned_tech_id' => $tecnico->id,
    ]);

    $response->assertStatus(201);
    // Idem: sendToDatabase (in-app) sigue firmando siempre, solo se apaga
    // el Web Push.
    Notification::assertNotSentTo($tecnico, PushNotification::class);
});

test('reasignar el tecnico de una orden manda push al tecnico nuevo', function () {
    Notification::fake();
    activarEventosPush(['orden_nueva_asignada']);

    $tecnicoViejo = User::factory()->create();
    $tecnicoViejo->assignRole('técnico');
    $tecnicoNuevo = User::factory()->create();
    $tecnicoNuevo->assignRole('técnico');

    $order = WorkOrder::factory()->create(['assigned_tech_id' => $tecnicoViejo->id]);

    $this->actingAs($this->admin, 'sanctum');
    $response = $this->putJson("/api/v1/work-orders/{$order->id}", [
        'assigned_tech_id' => $tecnicoNuevo->id,
    ]);

    $response->assertStatus(200);
    Notification::assertSentTo($tecnicoNuevo, PushNotification::class);
    Notification::assertNothingSentTo($tecnicoViejo);
});

test('editar una orden sin cambiar el tecnico asignado no manda push de nuevo', function () {
    Notification::fake();
    activarEventosPush(['orden_nueva_asignada']);

    $tecnico = User::factory()->create();
    $tecnico->assignRole('técnico');

    $order = WorkOrder::factory()->create(['assigned_tech_id' => $tecnico->id]);

    $this->actingAs($this->admin, 'sanctum');
    $response = $this->putJson("/api/v1/work-orders/{$order->id}", [
        'title' => 'Título actualizado',
        'assigned_tech_id' => $tecnico->id,
    ]);

    $response->assertStatus(200);
    Notification::assertNothingSentTo($tecnico);
});

// --- Endpoint de suscripción -----------------------------------------------

test('el endpoint de suscripcion push requiere autenticacion', function () {
    $response = $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => ['p256dh' => 'clave-publica', 'auth' => 'token-auth'],
    ]);

    $response->assertStatus(401);
});

test('un usuario autenticado puede registrar su suscripcion push', function () {
    $tecnico = User::factory()->create();
    // Sanctum::actingAs (no actingAs generico) porque el endpoint ahora
    // chequea tokenCan('push-subscriptions:manage') - necesita un token
    // simulado de verdad, no solo el guard con el usuario puesto.
    Sanctum::actingAs($tecnico, ['*']);

    $response = $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => ['p256dh' => 'clave-publica', 'auth' => 'token-auth'],
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $tecnico->id,
        'subscribable_type' => User::class,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ]);
});

test('un usuario autenticado puede borrar su suscripcion push', function () {
    $tecnico = User::factory()->create();
    $tecnico->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc123', 'clave-publica', 'token-auth');
    Sanctum::actingAs($tecnico, ['*']);

    $response = $this->deleteJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseMissing('push_subscriptions', [
        'subscribable_id' => $tecnico->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ]);
});

// Regresion 2026-09-18: el token que emite PushNotificationsWidget (panel
// Filament) esta escopeado a una sola ability para que, si se filtra desde
// el navegador, no sirva como token de acceso completo a la API.
test('un token escopeado solo a push-subscriptions:manage puede suscribirse', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Sanctum::actingAs($supervisor, ['push-subscriptions:manage']);

    $response = $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz789',
        'keys' => ['p256dh' => 'clave-publica', 'auth' => 'token-auth'],
    ]);

    $response->assertStatus(201);
});

test('un token sin la ability push-subscriptions:manage es rechazado', function () {
    $tecnico = User::factory()->create();
    Sanctum::actingAs($tecnico, ['otra-ability-cualquiera']);

    $response = $this->postJson('/api/v1/push-subscriptions', [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz789',
        'keys' => ['p256dh' => 'clave-publica', 'auth' => 'token-auth'],
    ]);

    $response->assertStatus(403);
});
