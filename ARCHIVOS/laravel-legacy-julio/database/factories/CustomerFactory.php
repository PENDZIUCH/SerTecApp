<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['individual', 'company', 'gym']);

        return [
            'customer_type' => $type,
            'business_name' => $type !== 'individual' ? fake()->company() : null,
            'first_name' => $type === 'individual' ? fake()->firstName() : null,
            'last_name' => $type === 'individual' ? fake()->lastName() : null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'tax_id' => fake()->numerify('##-########-#'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Argentina',
            'postal_code' => fake()->postcode(),
            'is_active' => true,
        ];
    }
}
