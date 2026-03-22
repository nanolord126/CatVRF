<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\BeautyConsumable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BeautyConsumable>
 */
final class BeautyConsumableFactory extends Factory
{
    protected $model = BeautyConsumable::class;

    public function definition(): array
    {
        $consumableNames = [
            'Перчатки одноразовые',
            'Краска для волос',
            'Шампунь',
            'Бальзам',
            'Полотенца',
            'Лак для ногтей',
            'Гель-лак',
            'Салфетки',
            'Ватные диски',
            'Масло для массажа',
        ];

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'salon_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'name' => $this->faker->randomElement($consumableNames),
            'description' => $this->faker->sentence(),
            'sku' => strtoupper($this->faker->bothify('CON-####-???')),
            'current_stock' => $this->faker->numberBetween(10, 500),
            'hold_stock' => 0,
            'min_stock_threshold' => $this->faker->numberBetween(5, 20),
            'unit_price' => $this->faker->numberBetween(5000, 50000), // 50-500 руб
            'unit_type' => $this->faker->randomElement(['шт', 'л', 'кг', 'упак']),
            'tags' => [
                $this->faker->randomElement(['essential', 'optional', 'premium']),
            ],
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => $this->faker->numberBetween(1, 5),
        ]);
    }
}
