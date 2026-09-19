<?php

use App\Models\Customer;

// Customer::updateEmailIfChanged() (2026-09-18): al completar un parte o
// crear/editar una orden, si el email de contacto viene distinto al que
// tenía el cliente, ese pasa a ser el vigente y el anterior NO se pierde -
// queda en secondary_email. Pedido de Hugo tras encontrar un cliente real
// con el email vacío en un caso donde casi se manda un aviso a un email
// viejo/de prueba.
test('actualiza el email y mueve el anterior a secundario', function () {
    $customer = Customer::factory()->create(['email' => 'viejo@ejemplo.com', 'secondary_email' => null]);

    $customer->updateEmailIfChanged('nuevo@ejemplo.com');

    expect($customer->fresh()->email)->toBe('nuevo@ejemplo.com');
    expect($customer->fresh()->secondary_email)->toBe('viejo@ejemplo.com');
});

test('no pisa el secundario si no habia email anterior (primera carga)', function () {
    $customer = Customer::factory()->create(['email' => null, 'secondary_email' => null]);

    $customer->updateEmailIfChanged('nuevo@ejemplo.com');

    expect($customer->fresh()->email)->toBe('nuevo@ejemplo.com');
    expect($customer->fresh()->secondary_email)->toBeNull();
});

test('no hace nada si el email es igual al que ya tenia', function () {
    $customer = Customer::factory()->create(['email' => 'igual@ejemplo.com', 'secondary_email' => 'otro@ejemplo.com']);

    $customer->updateEmailIfChanged('igual@ejemplo.com');

    expect($customer->fresh()->email)->toBe('igual@ejemplo.com');
    expect($customer->fresh()->secondary_email)->toBe('otro@ejemplo.com');
});

test('no hace nada si el email nuevo viene vacio', function () {
    $customer = Customer::factory()->create(['email' => 'original@ejemplo.com']);

    $customer->updateEmailIfChanged('');
    $customer->updateEmailIfChanged(null);

    expect($customer->fresh()->email)->toBe('original@ejemplo.com');
});

test('reemplazar un email por segunda vez pisa el secundario anterior', function () {
    $customer = Customer::factory()->create(['email' => 'uno@ejemplo.com', 'secondary_email' => null]);

    $customer->updateEmailIfChanged('dos@ejemplo.com');
    $customer->refresh();
    $customer->updateEmailIfChanged('tres@ejemplo.com');

    expect($customer->fresh()->email)->toBe('tres@ejemplo.com');
    expect($customer->fresh()->secondary_email)->toBe('dos@ejemplo.com');
});
