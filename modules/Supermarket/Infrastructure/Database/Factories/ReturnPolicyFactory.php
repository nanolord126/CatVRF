<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\ReturnPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReturnPolicyFactory extends Factory
{
    protected $model = ReturnPolicy::class;

    public function definition(): array
    {
        return [
            'sub_vertical' => fake()->randomElement([
                'meat_shops', 'vegan_products', 'confectionery', 'farm_direct',
                'grocery_and_delivery', 'office_catering', 'baby_food', 'pet_food'
            ]),
            'vertical' => 'supermarket',
            'max_days' => fake()->numberBetween(1, 14),
            'allowed_reasons' => fake()->randomElements(['spoiled', 'wrong_item', 'changed_mind', 'damaged', 'other'], fake()->numberBetween(2, 5)),
            'cold_chain_only_defect' => fake()->boolean(50),
            'requires_photo' => fake()->boolean(70),
            'requires_temperature' => fake()->boolean(30),
            'max_refund_percent' => fake()->numberBetween(50, 100),
            'is_active' => true,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function coldChainStrict(): self
    {
        return $this->state(fn (array $attributes) => [
            'cold_chain_only_defect' => true,
            'requires_photo' => true,
            'requires_temperature' => true,
            'max_days' => fake()->numberBetween(1, 2),
        ]);
    }
}
