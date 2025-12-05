<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetItemFactory extends Factory
{
    protected $model = BudgetItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 50, 1000);

        return [
            'budget_id' => Budget::factory(),
            'item_type' => fake()->randomElement(['service', 'part']),
            'description' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $quantity * $unitPrice,
        ];
    }
}
