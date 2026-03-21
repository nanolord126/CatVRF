<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domains\Advertising\AdCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

final class AdCampaignFactory extends Factory
{
    protected $model = AdCampaign::class;

    public function definition(): array
    {
        $budget = fake()->numberBetween(1000, 10000);
        $spent = fake()->numberBetween(0, $budget);

        $tenant_id = DB::table("tenants")->value("id") ?? 1;
        $user_id = DB::table("users")->value("id") ?? 1;

        return [
            "tenant_id" => $tenant_id,
            "advertiser_id" => $user_id,
            "title" => fake()->sentence(),
            "description" => fake()->paragraph(),
            "status" => "active",
            "budget" => $budget,
            "spent" => $spent,
            "start_date" => now(),
            "end_date" => now()->addMonth(),
        ];
    }
}
