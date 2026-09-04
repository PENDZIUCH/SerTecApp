<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hallazgos CRITICOS de la auditoria del 2026-09-04: estos 4 endpoints eran
 * 100% publicos (el codigo tenia el comentario "SIN AUTH para testing").
 * Cualquiera sin cuenta podia leer PII de clientes/firmas, forjar el cierre
 * de una orden, o generar un token de acceso total a cualquier usuario.
 */
class PublicEndpointsClosedTest extends TestCase
{
    private User $tecnicoDueno;
    private User $otroTecnico;
    private User $administrador;
    private WorkOrder $orden;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'administrador', 'supervisor', 'técnico'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $this->tecnicoDueno = User::factory()->create();
        $this->tecnicoDueno->assignRole('técnico');

        $this->otroTecnico = User::factory()->create();
        $this->otroTecnico->assignRole('técnico');

        $this->administrador = User::factory()->create();
        $this->administrador->assignRole('administrador');

        $this->orden = WorkOrder::factory()->create(['assigned_tech_id' => $this->tecnicoDueno->id]);
    }

    public function test_technician_endpoints_reject_anonymous_requests(): void
    {
        $this->getJson("/api/v1/ordenes/tecnico/{$this->tecnicoDueno->id}")->assertStatus(401);
        $this->getJson('/api/v1/partes/1')->assertStatus(401);
        $this->postJson('/api/v1/partes', [])->assertStatus(401);
    }

    public function test_magic_link_generate_rejects_anonymous_requests(): void
    {
        $this->postJson('/api/v1/magic-link/generate', ['user_id' => $this->tecnicoDueno->id])
            ->assertStatus(401);
    }

    public function test_technician_cannot_read_another_technicians_orders(): void
    {
        Sanctum::actingAs($this->otroTecnico);

        $this->getJson("/api/v1/ordenes/tecnico/{$this->tecnicoDueno->id}")->assertStatus(403);
    }

    public function test_technician_can_read_own_orders(): void
    {
        Sanctum::actingAs($this->tecnicoDueno);

        $this->getJson("/api/v1/ordenes/tecnico/{$this->tecnicoDueno->id}")->assertStatus(200);
    }

    public function test_technician_cannot_submit_a_parte_impersonating_another_technician(): void
    {
        Sanctum::actingAs($this->otroTecnico);

        $response = $this->postJson('/api/v1/partes', [
            'orden_id' => $this->orden->id,
            'tecnico_id' => $this->tecnicoDueno->id,
            'diagnostico' => 'x',
            'trabajo_realizado' => 'x',
            'firma_base64' => str_repeat('a', 120),
        ]);

        $response->assertStatus(403);
    }

    public function test_technician_cannot_generate_a_magic_link_for_anyone(): void
    {
        Sanctum::actingAs($this->tecnicoDueno);

        $this->postJson('/api/v1/magic-link/generate', ['user_id' => $this->administrador->id])
            ->assertStatus(403);
    }

    public function test_admin_tier_can_generate_a_magic_link(): void
    {
        Sanctum::actingAs($this->administrador);

        $this->postJson('/api/v1/magic-link/generate', ['user_id' => $this->tecnicoDueno->id])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
