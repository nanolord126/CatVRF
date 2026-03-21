<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Furniture\Models\Furniture;
use Illuminate\Database\Eloquent\Factories\Factory;

final class FurnitureFactory extends Factory
{
    protected $model = Furniture::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->randomElement([
                'Диван Честерфилд',
                'Кресло Папасан',
                'Стол обеденный дерево',
                'Комод керамический',
                'Кровать платформа',
                'Шкаф купе',
                'Полка настенная',
                'Тумба прикроватная',
            ]),
            'sku' => 'FRN-' . strtoupper($this->faker->lexify('???')),
            'category' => $this->faker->randomElement(['sofa', 'chair', 'table', 'bed', 'cabinet', 'shelf']),
            'material' => $this->faker->randomElement(['wood', 'metal', 'leather', 'fabric', 'ceramic']),
            'price' => $this->faker->numberBetween(50000, 500000),
            'current_stock' => $this->faker->numberBetween(2, 50),
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            'is_available' => true,
            'tags' => null,
        ];
    }

    public function luxury(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(250000, 500000),
            'material' => $this->faker->randomElement(['leather', 'ceramic', 'wood']),
            'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
        ]);
    }

    public function budget(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(50000, 150000),
            'material' => $this->faker->randomElement(['fabric', 'metal']),
            'rating' => $this->faker->randomFloat(1, 3.5, 4.5),
        ]);
    }
}
