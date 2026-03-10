<?php

namespace Database\Factories;

use App\Models\Domains\Advertising\AdCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdCampaignFactory extends Factory
{
    protected $model = AdCampaign::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'advertiser_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'active', 'paused', 'ended']),
            'budget' => $this->faker->numberBetween(1000, 100000),
            'spent' => $this->faker->numberBetween(0, 10000),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->dateTimeBetween('+1 days', '+30 days'),
        ];
    }
}
