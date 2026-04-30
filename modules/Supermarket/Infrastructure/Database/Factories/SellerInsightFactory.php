<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\SellerInsight;
use Illuminate\Database\Eloquent\Factories\Factory;

class SellerInsightFactory extends Factory
{
    protected $model = SellerInsight::class;

    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'type' => fake()->randomElement([
                'revenue_growth', 'top_product', 'pricing_recommendation',
                'return_problem', 'category_performance', 'general'
            ]),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'value' => fake()->randomFloat(2, -50, 100),
            'impact' => fake()->randomElement(['high', 'medium', 'low']),
            'actionable' => fake()->boolean(80),
            'generated_at' => now()->subHours(fake()->numberBetween(1, 36)),
            'expires_at' => now()->addHours(fake()->numberBetween(1, 36)),
        ];
    }

    public function highImpact(): self
    {
        return $this->state(fn (array $attributes) => [
            'impact' => 'high',
        ]);
    }

    public function mediumImpact(): self
    {
        return $this->state(fn (array $attributes) => [
            'impact' => 'medium',
        ]);
    }

    public function lowImpact(): self
    {
        return $this->state(fn (array $attributes) => [
            'impact' => 'low',
        ]);
    }

    public function actionable(): self
    {
        return $this->state(fn (array $attributes) => [
            'actionable' => true,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(fake()->numberBetween(1, 36)),
        ]);
    }
}
