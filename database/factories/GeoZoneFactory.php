<?php

namespace Database\Factories;

use App\Models\Domains\Geo\GeoZone;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeoZoneFactory extends Factory
{
    protected $model = GeoZone::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'name' => $this->faker->word(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'radius_km' => $this->faker->numberBetween(1, 50),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
