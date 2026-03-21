<?php

declare(strict_types=1);

namespace Database\Factories\MedicalSupplies;

use App\Domains\MedicalSupplies\Models\MedicalSupply;
use Illuminate\Database\Eloquent\Factories\Factory;

final class MedicalSupplyFactory extends Factory
{
    protected $model = MedicalSupply::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'sku' => strtoupper($this->faker->bothify('MED-####')),
            'category' => $this->faker->randomElement(['equipment', 'consumables', 'bandages', 'syringes', 'instruments', 'medications']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(10000, 200000),
            'current_stock' => $this->faker->numberBetween(50, 500),
            'min_stock_threshold' => $this->faker->numberBetween(10, 50),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['medical', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function equipment(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'equipment',
                'price' => $this->faker->numberBetween(50000, 200000),
            ];
        });
    }

    public function consumables(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'category' => 'consumables',
                'price' => $this->faker->numberBetween(10000, 50000),
            ];
        });
    }
}
