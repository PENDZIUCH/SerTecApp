<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkOrderFactory extends Factory
{
    protected $model = WorkOrder::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'equipment_id' => Equipment::factory(),
            'wo_number' => 'WO-' . fake()->unique()->numerify('########'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed']),
            'assigned_tech_id' => User::factory(),
            'scheduled_date' => fake()->dateTimeBetween('now', '+1 month'),
            'labor_cost' => fake()->randomFloat(2, 500, 5000),
            'parts_cost' => fake()->randomFloat(2, 0, 2000),
            'total_cost' => 0,
            'created_by' => User::factory(),
        ];
    }
}
