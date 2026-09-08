<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Bug real encontrado a mano el 2026-09-08 probando produccion con curl sin
 * header Accept: application/json (los helpers getJson()/postJson() de los
 * tests SI lo mandan solos, por eso el resto de la suite nunca lo detecto).
 * Sin el fix en bootstrap/app.php, cualquier request a /api/* sin ese header
 * caia en el manejo "web" de Laravel -> redirect()->guest(route('login')) ->
 * la ruta 'login' no existe en esta app -> 500 en vez de 401 JSON.
 */
class ApiErrorsAlwaysJsonTest extends TestCase
{
    public function test_protected_api_route_returns_json_401_without_accept_header(): void
    {
        // get() en vez de getJson(): simula un cliente que no manda
        // "Accept: application/json" (curl plano, un bot, etc.)
        $response = $this->get('/api/v1/ordenes/tecnico/1');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_protected_api_post_route_returns_json_401_without_accept_header(): void
    {
        $response = $this->post('/api/v1/magic-link/generate');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }
}
