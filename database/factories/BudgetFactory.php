<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'title' => fake()->sentence(5),
            'status' => fake()->randomElement(['draft', 'sent', 'approved']),
            'valid_until' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'tax_percent' => 21.00,
            'discount_type' => fake()->randomElement(['none', 'percent', 'amount']),
            'discount_value' => fake()->randomFloat(2, 0, 500),
            'subtotal_services' => fake()->randomFloat(2, 1000, 5000),
            'subtotal_parts' => fake()->randomFloat(2, 500, 2000),
            'created_by' => User::factory(),
        ];
    }
}
