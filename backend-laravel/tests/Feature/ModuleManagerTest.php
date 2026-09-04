<?php

namespace Tests\Feature;

use App\Filament\Resources\BudgetResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\EquipmentResource;
use App\Filament\Resources\PartResource;
use App\Filament\Resources\SubscriptionResource;
use App\Filament\Resources\VisitResource;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Resources\WorkPartResource;
use App\Filament\Resources\WorkshopItemResource;
use App\Services\ModuleManager;
use Tests\TestCase;

/**
 * El sistema de modulos on/off portado desde core/v1 el 2026-09-04. Lo
 * importante a proteger: default TODO activo (cero impacto en una
 * instalacion existente que nunca toco esta pantalla), y que apagar un
 * modulo realmente bloquee canAccess() del Resource correspondiente.
 */
class ModuleManagerTest extends TestCase
{
    public function test_all_modules_are_active_by_default_when_no_setting_exists(): void
    {
        foreach (['customers', 'work_orders', 'visits', 'budgets', 'parts', 'workshop', 'subscriptions', 'equipment'] as $module) {
            $this->assertTrue(ModuleManager::isActive($module), "$module debería estar activo por default");
        }
    }

    public function test_deactivating_a_module_blocks_canAccess_on_its_resource(): void
    {
        $map = [
            'customers' => CustomerResource::class,
            'equipment' => EquipmentResource::class,
            'parts' => PartResource::class,
            'subscriptions' => SubscriptionResource::class,
            'visits' => VisitResource::class,
            'work_orders' => WorkOrderResource::class,
            'workshop' => WorkshopItemResource::class,
            'budgets' => BudgetResource::class,
        ];

        foreach ($map as $module => $resourceClass) {
            $this->assertTrue($resourceClass::canAccess(), "$module activo -> canAccess debería ser true");

            ModuleManager::deactivate($module);
            $this->assertFalse($resourceClass::canAccess(), "$module desactivado -> canAccess debería ser false");

            ModuleManager::activate($module);
            $this->assertTrue($resourceClass::canAccess(), "$module reactivado -> canAccess debería volver a true");
        }
    }

    public function test_work_order_and_work_part_share_the_same_module_key(): void
    {
        ModuleManager::deactivate('work_orders');

        $this->assertFalse(WorkOrderResource::canAccess());
        $this->assertFalse(WorkPartResource::canAccess());

        ModuleManager::activate('work_orders');

        $this->assertTrue(WorkOrderResource::canAccess());
        $this->assertTrue(WorkPartResource::canAccess());
    }

    public function test_deactivating_one_module_does_not_affect_others(): void
    {
        ModuleManager::deactivate('equipment');

        $this->assertFalse(ModuleManager::isActive('equipment'));
        $this->assertTrue(ModuleManager::isActive('customers'));
        $this->assertTrue(ModuleManager::isActive('work_orders'));
        $this->assertTrue(CustomerResource::canAccess());
    }
}
