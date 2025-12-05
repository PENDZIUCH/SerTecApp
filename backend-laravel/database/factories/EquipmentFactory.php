<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentBrand;
use App\Models\EquipmentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'brand_id' => EquipmentBrand::factory(),
            'model_id' => EquipmentModel::factory(),
            'serial_number' => fake()->bothify('SN-####-????'),
            'equipment_code' => fake()->bothify('EQ-####'),
            'purchase_date' => fake()->dateTimeBetween('-2 years', '-1 month'),
            'installation_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'warranty_expiration' => fake()->dateTimeBetween('now', '+2 years'),
            'next_service_date' => fake()->dateTimeBetween('now', '+6 months'),
            'location' => fake()->city(),
            'status' => fake()->randomElement(['active', 'inactive', 'in_workshop']),
            'notes' => fake()->sentence(),
        ];
    }
}
