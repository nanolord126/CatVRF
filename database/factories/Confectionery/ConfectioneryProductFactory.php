<?php

declare(strict_types=1);

namespace Database\Factories\Confectionery;

use App\Domains\Confectionery\Models\ConfectioneryProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ConfectioneryProductFactory extends Factory
{
    protected $model = ConfectioneryProduct::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'sku' => strtoupper($this->faker->bothify('CONF-####')),
            'category' => $this->faker->randomElement(['cake', 'pastry', 'chocolate', 'candy', 'biscuit', 'cookies']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(50000, 150000),
            'current_stock' => $this->faker->numberBetween(30, 200),
            'shelf_life_days' => $this->faker->numberBetween(5, 60),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['confectionery', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function luxury(): self
    {
        return $this->state(['price' => $this->faker->numberBetween(100000, 200000), 'shelf_life_days' => 7]);
    }

    public function budget(): self
    {
        return $this->state(['price' => $this->faker->numberBetween(50000, 80000), 'shelf_life_days' => 14]);
    }
}
