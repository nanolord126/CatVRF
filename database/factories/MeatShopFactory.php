<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\MeatShops\Models\MeatShop;
use Illuminate\Database\Eloquent\Factories\Factory;

final class MeatShopFactory extends Factory
{
    protected $model = MeatShop::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->randomElement([
                'Говядина премиум срезы',
                'Свинина маринованная',
                'Курица домашняя',
                'Баранина ягненка',
                'Колбаса местная',
                'Фарш мясной смешанный',
                'Стейк Рибай',
                'Грудка куриная филе',
            ]),
            'sku' => 'MSH-' . strtoupper($this->faker->lexify('???')),
            'meat_type' => $this->faker->randomElement(['beef', 'pork', 'chicken', 'lamb', 'mixed']),
            'cut' => $this->faker->randomElement(['steak', 'fillet', 'ground', 'ribs', 'shoulder']),
            'weight_g' => $this->faker->randomElement([250, 500, 750, 1000]),
            'price' => $this->faker->numberBetween(80000, 400000),
            'current_stock' => $this->faker->numberBetween(10, 100),
            'is_certified' => true,
            'rating' => $this->faker->randomFloat(1, 4.2, 5.0),
            'tags' => null,
        ];
    }

    public function premium(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(250000, 400000),
            'weight_g' => $this->faker->randomElement([750, 1000]),
            'rating' => $this->faker->randomFloat(1, 4.7, 5.0),
        ]);
    }

    public function budget(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(80000, 150000),
            'weight_g' => 500,
            'rating' => $this->faker->randomFloat(1, 3.8, 4.5),
        ]);
    }
}
