<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkshopItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkshopItemFactory extends Factory
{
    protected $model = WorkshopItem::class;

    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'customer_id' => Customer::factory(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'entry_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'estimated_completion_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'assigned_tech_id' => User::factory(),
            'description' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
