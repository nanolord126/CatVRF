<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domains\Insurance\InsurancePolicy;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

final class InsurancePolicyFactory extends Factory
{
    protected $model = InsurancePolicy::class;

    public function definition(): array
    {
        return [
            "tenant_id" => \Illuminate\Support\Facades\DB::table("tenants")->inRandomOrder()->value("id") ?? Tenant::factory(),
            "number" => fake()->unique()->bothify("POL-####"),
            "type" => fake()->randomElement(["health", "auto", "home", "life"]),
            "premium_amount" => fake()->numberBetween(1000, 5000),
            "expires_at" => now()->addYear(),
        ];
    }
}

