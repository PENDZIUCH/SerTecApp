<?php

namespace Database\Factories;

use App\Models\LookupValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LookupValue>
 */
class LookupValueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => 'customer_type',
            'value' => $this->faker->unique()->word(),
            'label' => $this->faker->words(2, true),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
