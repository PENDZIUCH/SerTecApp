<?php

namespace Database\Factories;

use App\Models\EquipmentBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentBrandFactory extends Factory
{
    protected $model = EquipmentBrand::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'country' => fake()->country(),
            'website' => fake()->url(),
        ];
    }
}
