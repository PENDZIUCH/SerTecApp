<?php

namespace Database\Factories;

use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentModelFactory extends Factory
{
    protected $model = EquipmentModel::class;

    public function definition(): array
    {
        return [
            'brand_id' => EquipmentBrand::factory(),
            'name' => fake()->word() . ' ' . fake()->randomNumber(4),
            'model_code' => fake()->bothify('??-####'),
            'category' => fake()->randomElement(['treadmill', 'bike', 'rower', 'elliptical']),
            'description' => fake()->sentence(),
        ];
    }
}
