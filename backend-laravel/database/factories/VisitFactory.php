<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Visit;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitFactory extends Factory
{
    protected $model = Visit::class;

    public function definition(): array
    {
        return [
            'work_order_id' => WorkOrder::factory(),
            'assigned_tech_id' => User::factory(),
            'visit_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'scheduled_time' => fake()->time('H:i'),
            'estimated_duration_minutes' => fake()->numberBetween(30, 240),
            'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed']),
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
