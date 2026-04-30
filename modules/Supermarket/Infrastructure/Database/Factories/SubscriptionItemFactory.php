<?php

declare(strict_types=1);

namespace Modules\Supermarket\Database\Factories;

use Modules\Supermarket\Infrastructure\Models\SubscriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionItemFactory extends Factory
{
    protected $model = SubscriptionItem::class;

    public function definition(): array
    {
        return [
            'subscription_id' => \Modules\Supermarket\Infrastructure\Models\Subscription::factory(),
            'product_id' => \App\Models\Product::factory(),
            'variant_id' => fake()->boolean(20) ? \App\Models\ProductVariant::factory() : null,
            'quantity' => fake()->numberBetween(1, 5),
            'price_per_unit_at_creation' => fake()->randomFloat(2, 100, 2000) * 100, // в копейках
        ];
    }
}
