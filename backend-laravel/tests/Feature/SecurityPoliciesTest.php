<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\Part;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkOrder;
use App\Models\WorkshopItem;
use App\Services\ModuleManager;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Congela el comportamiento de las Policies reescritas en la auditoria de
 * seguridad del 2026-09-04: administrador/super_admin/supervisor mantienen
 * acceso total, un tecnico solo toca sus propios registros (en los modelos
 * con assigned_tech_id) o solo puede ver (en los que no tienen dueno), y
 * nunca puede borrar via API. Si alguien reintroduce el hueco original
 * (options()/canX() sin filtrar por rol o por pertenencia), estos tests
 * fallan.
 */
class SecurityPoliciesTest extends TestCase
{
    private User $superAdmin;
    private User $administrador;
    private User $supervisor;
    private User $tecnicoDueno;
    private User $otroTecnico;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'administrador', 'supervisor', 'técnico'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->administrador = User::factory()->create();
        $this->administrador->assignRole('administrador');

        $this->supervisor = User::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->tecnicoDueno = User::factory()->create();
        $this->tecnicoDueno->assignRole('técnico');

        $this->otroTecnico = User::factory()->create();
        $this->otroTecnico->assignRole('técnico');
    }

    private function ownedModels(): array
    {
        return [
            'WorkOrder' => fn () => WorkOrder::factory()->create(['assigned_tech_id' => $this->tecnicoDueno->id]),
            'Visit' => fn () => Visit::factory()->create(['assigned_tech_id' => $this->tecnicoDueno->id]),
            'WorkshopItem' => fn () => WorkshopItem::factory()->create(['assigned_tech_id' => $this->tecnicoDueno->id]),
        ];
    }

    public function test_admin_tier_roles_keep_full_access_on_owned_models(): void
    {
        foreach ($this->ownedModels() as $label => $factory) {
            $record = $factory();
            foreach ([$this->superAdmin, $this->administrador, $this->supervisor] as $actor) {
                $this->assertTrue($actor->can('view', $record), "$label: $actor->id debería poder ver");
                $this->assertTrue($actor->can('update', $record), "$label: $actor->id debería poder editar");
                $this->assertTrue($actor->can('delete', $record), "$label: $actor->id debería poder borrar");
            }
        }
    }

    public function test_technician_can_only_touch_own_record_never_delete(): void
    {
        foreach ($this->ownedModels() as $label => $factory) {
            $record = $factory();

            $this->assertTrue($this->tecnicoDueno->can('view', $record), "$label: el dueño debería poder ver");
            $this->assertTrue($this->tecnicoDueno->can('update', $record), "$label: el dueño debería poder editar");
            $this->assertFalse($this->tecnicoDueno->can('delete', $record), "$label: el dueño NUNCA debería poder borrar via API");

            $this->assertFalse($this->otroTecnico->can('view', $record), "$label: IDOR - otro tecnico no debería poder ver");
            $this->assertFalse($this->otroTecnico->can('update', $record), "$label: IDOR - otro tecnico no debería poder editar");
            $this->assertFalse($this->otroTecnico->can('delete', $record), "$label: otro tecnico no debería poder borrar");
        }
    }

    private function readOnlyModelsForTechnician(): array
    {
        return [
            'Customer' => fn () => Customer::factory()->create(),
            'Part' => fn () => Part::factory()->create(),
            'Equipment' => fn () => Equipment::factory()->create(),
            'Budget' => fn () => Budget::factory()->create(),
        ];
    }

    public function test_technician_has_read_only_access_where_there_is_no_ownership(): void
    {
        foreach ($this->readOnlyModelsForTechnician() as $label => $factory) {
            $record = $factory();

            $this->assertTrue($this->tecnicoDueno->can('view', $record), "$label: tecnico debería poder ver (lo necesita para trabajar)");
            $this->assertFalse($this->tecnicoDueno->can('update', $record), "$label: tecnico NO debería poder editar");
            $this->assertFalse($this->tecnicoDueno->can('delete', $record), "$label: tecnico NO debería poder borrar");

            foreach ([$this->superAdmin, $this->administrador, $this->supervisor] as $actor) {
                $this->assertTrue($actor->can('update', $record), "$label: admin-tier debería poder editar");
                $this->assertTrue($actor->can('delete', $record), "$label: admin-tier debería poder borrar");
            }
        }
    }

    public function test_technician_has_no_access_to_subscriptions(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertFalse($this->tecnicoDueno->can('viewAny', Subscription::class), 'tecnico no debería ni listar suscripciones');
        $this->assertFalse($this->tecnicoDueno->can('view', $subscription), 'tecnico no debería ver una suscripción puntual');

        foreach ([$this->superAdmin, $this->administrador, $this->supervisor] as $actor) {
            $this->assertTrue($actor->can('view', $subscription), 'admin-tier debería ver suscripciones');
        }
    }
}
