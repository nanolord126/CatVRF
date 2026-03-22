<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\BeautyProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BeautyProduct>
 */
final class BeautyProductFactory extends Factory
{
    protected $model = BeautyProduct::class;

    public function definition(): array
    {
        $productNames = [
            'Шампунь профессиональный',
            'Крем для лица',
            'Маска для волос',
            'Сыворотка омолаживающая',
            'Набор кистей для макияжа',
            'Фен профессиональный',
            'Гель для душа',
            'Парфюм',
            'Расческа массажная',
            'Лак для волос',
        ];

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'salon_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'name' => $this->faker->randomElement($productNames),
            'description' => $this->faker->paragraph(),
            'sku' => strtoupper($this->faker->bothify('PROD-####-???')),
            'current_stock' => $this->faker->numberBetween(5, 100),
            'price' => $this->faker->numberBetween(50000, 500000), // 500-5000 руб
            'is_active' => $this->faker->boolean(95),
            'tags' => [
                $this->faker->randomElement(['professional', 'retail', 'bestseller']),
            ],
        ];
    }
}
