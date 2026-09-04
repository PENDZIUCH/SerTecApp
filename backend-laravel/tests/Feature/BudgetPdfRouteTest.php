<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bug preexistente encontrado en la auditoria del 2026-09-04 (no era un
 * hueco de seguridad, igual bloqueaba el acceso, pero daba 500 en vez de
 * un redirect prolijo): el middleware('auth') por defecto intenta
 * redirigir a una ruta nombrada 'login' que no existe en esta app (solo
 * existe la del panel Filament) -> RouteNotFoundException -> 500.
 */
class BudgetPdfRouteTest extends TestCase
{
    public function test_anonymous_user_is_redirected_to_login_not_500(): void
    {
        $budget = Budget::factory()->create();

        $response = $this->get(route('budgets.pdf', $budget));

        $response->assertRedirect(route('filament.sertecapp.auth.login'));
    }

    public function test_technician_cannot_download_any_budget_pdf(): void
    {
        Role::create(['name' => 'técnico', 'guard_name' => 'web']);
        $tecnico = User::factory()->create();
        $tecnico->assignRole('técnico');
        $budget = Budget::factory()->create();

        $this->actingAs($tecnico)
            ->get(route('budgets.pdf', $budget))
            ->assertStatus(403);
    }

    public function test_admin_tier_can_download_a_budget_pdf(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $budget = Budget::factory()->create();

        $this->actingAs($admin)
            ->get(route('budgets.pdf', $budget))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }
}
