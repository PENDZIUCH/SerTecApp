<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'plan_name' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'visits_per_period' => fake()->numberBetween(4, 12),
            'visits_used' => fake()->numberBetween(0, 5),
            'billing_cycle' => fake()->randomElement(['monthly', 'quarterly', 'yearly']),
            'renewal_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'grace_period_days' => 7,
            'auto_renew' => true,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
