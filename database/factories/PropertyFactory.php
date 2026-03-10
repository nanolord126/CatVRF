<?php

namespace Database\Factories;

use App\Models\Domains\RealEstate\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'owner_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 4),
            'area_sqm' => $this->faker->numberBetween(50, 300),
            'price_per_night' => $this->faker->numberBetween(100, 1000),
            'status' => $this->faker->randomElement(['available', 'booked', 'inactive']),
        ];
    }
}
