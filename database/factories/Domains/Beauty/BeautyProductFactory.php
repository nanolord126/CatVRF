<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Beauty;

use App\Domains\Beauty\Models\BeautyProduct;
use App\Domains\Beauty\Models\BeautySalon;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class BeautyProductFactory extends Factory
{
    protected $model = BeautyProduct::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'beauty_salon_id' => BeautySalon::factory(),
            'name' => $this->faker->words(3, true),
            'sku' => $this->faker->unique()->ean13(),
            'description' => $this->faker->realText(200),
            'price' => $this->faker->numberBetween(1000, 10000), // Цена в копейках
            'current_stock' => $this->faker->numberBetween(0, 100),
            'tags' => json_encode([$this->faker->word(), $this->faker->word()]),
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
