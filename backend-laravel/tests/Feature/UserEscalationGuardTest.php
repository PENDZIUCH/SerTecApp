<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hueco cerrado el 2026-09-04: un administrador (no super_admin) podia
 * autoasignarse o asignarle a cualquier usuario el rol super_admin via
 * POST/PUT /api/v1/users, sin pasar por el panel de Filament. Estos tests
 * fijan que StoreUserRequest/UpdateUserRequest lo rechacen.
 */
class UserEscalationGuardTest extends TestCase
{
    private User $administrador;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'administrador', 'técnico'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $this->administrador = User::factory()->create();
        $this->administrador->assignRole('administrador');

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_administrador_cannot_create_a_super_admin_user(): void
    {
        Sanctum::actingAs($this->administrador);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Intento Escalada',
            'email' => 'escalada@example.com',
            'password' => 'password123',
            'roles' => ['super_admin'],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'escalada@example.com']);
    }

    public function test_super_admin_can_create_another_super_admin_user(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Nuevo Super Admin',
            'email' => 'nuevo-super@example.com',
            'password' => 'password123',
            'roles' => ['super_admin'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'nuevo-super@example.com']);
    }

    public function test_administrador_cannot_promote_existing_user_to_super_admin(): void
    {
        $tecnico = User::factory()->create();
        $tecnico->assignRole('técnico');

        Sanctum::actingAs($this->administrador);

        $response = $this->putJson("/api/v1/users/{$tecnico->id}", [
            'roles' => ['super_admin'],
        ]);

        $response->assertStatus(422);
        $this->assertFalse($tecnico->fresh()->hasRole('super_admin'));
    }

    public function test_administrador_cannot_edit_an_existing_super_admin_account(): void
    {
        $otroSuperAdmin = User::factory()->create();
        $otroSuperAdmin->assignRole('super_admin');

        Sanctum::actingAs($this->administrador);

        $response = $this->putJson("/api/v1/users/{$otroSuperAdmin->id}", [
            'name' => 'Nombre cambiado sin permiso',
        ]);

        $response->assertStatus(403);
        $this->assertNotEquals('Nombre cambiado sin permiso', $otroSuperAdmin->fresh()->name);
    }

    public function test_administrador_cannot_list_or_delete_users_without_admin_tier_role(): void
    {
        $tecnico = User::factory()->create();
        $tecnico->assignRole('técnico');
        Sanctum::actingAs($tecnico);

        $this->getJson('/api/v1/users')->assertStatus(403);
        $this->deleteJson("/api/v1/users/{$this->administrador->id}")->assertStatus(403);
    }
}
