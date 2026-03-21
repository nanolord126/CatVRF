<?php

declare(strict_types=1);

namespace Database\Factories\AutoParts;

use App\Domains\AutoParts\Models\AutoPart;
use Illuminate\Database\Eloquent\Factories\Factory;

final class AutoPartFactory extends Factory
{
    protected $model = AutoPart::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('PART-####')),
            'category' => $this->faker->randomElement(['engine', 'suspension', 'brakes', 'electrical', 'body', 'interior', 'accessories']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(50000, 300000),
            'current_stock' => $this->faker->numberBetween(20, 150),
            'status' => 'active',
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['autopart', $this->faker->word()],
            'meta' => [],
        ];
    }

    public function oem(): self
    {
        return $this->state(['price' => $this->faker->numberBetween(100000, 300000)]);
    }

    public function aftermarket(): self
    {
        return $this->state(['price' => $this->faker->numberBetween(50000, 100000)]);
    }
}
