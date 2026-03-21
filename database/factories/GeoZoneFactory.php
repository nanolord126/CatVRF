<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GeoZone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

final class GeoZoneFactory extends Factory
{
    protected $model = GeoZone::class;

    public function definition(): array
    {
        return [
            "tenant_id" => DB::table("tenants")->value("id") ?? 1,
            "name" => fake()->unique()->city() . " Zone",
            "latitude" => fake()->latitude(),
            "longitude" => fake()->longitude(),
            "radius_km" => fake()->numberBetween(1, 50),
            "status" => "active",
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            "status" => "active",
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            "status" => "inactive",
        ]);
    }
}
