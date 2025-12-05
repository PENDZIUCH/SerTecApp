<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use App\Models\Part;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkOrder;
use App\Models\WorkshopItem;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating test data...');

        $admin = User::where('email', 'admin@sertecapp.local')->first();
        $technician = User::where('email', 'tecnico@sertecapp.local')->first();

        $customers = Customer::factory(20)->create();
        $this->command->info('✅ 20 customers created');

        $brands = EquipmentBrand::factory(5)->create();
        $models = EquipmentModel::factory(10)
            ->recycle($brands)
            ->create();

        $equipments = Equipment::factory(50)
            ->recycle($customers)
            ->recycle($brands)
            ->recycle($models)
            ->create();
        $this->command->info('✅ 50 equipments created');

        $workOrders = WorkOrder::factory(40)
            ->recycle($customers)
            ->recycle($equipments)
            ->recycle([$admin, $technician])
            ->create();
        $this->command->info('✅ 40 work orders created');

        Visit::factory(25)
            ->recycle($workOrders)
            ->recycle([$admin, $technician])
            ->create();
        $this->command->info('✅ 25 visits created');

        Part::factory(20)->create();
        $this->command->info('✅ 20 parts created');

        Subscription::factory(5)
            ->recycle($customers)
            ->recycle([$admin])
            ->create();
        $this->command->info('✅ 5 subscriptions created');

        WorkshopItem::factory(10)
            ->recycle($equipments)
            ->recycle($customers)
            ->recycle([$admin, $technician])
            ->create();
        $this->command->info('✅ 10 workshop items created');

        $budgets = Budget::factory(10)
            ->recycle($customers)
            ->recycle([$admin])
            ->create();

        foreach ($budgets as $budget) {
            BudgetItem::factory(rand(2, 5))
                ->for($budget)
                ->create();
        }
        $this->command->info('✅ 10 budgets with items created');

        $this->command->info('🎉 Test data seeding completed!');
    }
}
