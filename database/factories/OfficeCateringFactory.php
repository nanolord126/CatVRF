<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\OfficeCatering\Models\OfficeCatering;
use Illuminate\Database\Eloquent\Factories\Factory;

final class OfficeCateringFactory extends Factory
{
    protected $model = OfficeCatering::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->randomElement([
                'Бизнес-ланч базовый',
                'Обеденный набор премиум',
                'Завтрак Корпоратив',
                'Комбо обед выходного дня',
                'Паек Регулярный',
                'Обед Йоги/ПП',
                'Ужин Вечерний для офиса',
                'Снеки Рабочий день',
            ]),
            'sku' => 'OC-' . strtoupper($this->faker->lexify('???')),
            'meal_type' => $this->faker->randomElement(['breakfast', 'lunch', 'dinner', 'snacks', 'combo']),
            'servings' => $this->faker->numberBetween(1, 100),
            'price_per_serving' => $this->faker->numberBetween(200, 500),
            'total_price' => $this->faker->numberBetween(100000, 300000),
            'current_stock' => $this->faker->numberBetween(5, 50),
            'min_order' => $this->faker->numberBetween(1, 10),
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            'tags' => null,
        ];
    }

    public function corporate(): self
    {
        return $this->state(fn (array $attributes) => [
            'servings' => $this->faker->numberBetween(20, 100),
            'price_per_serving' => $this->faker->numberBetween(300, 500),
            'min_order' => $this->faker->numberBetween(5, 10),
            'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
        ]);
    }

    public function personal(): self
    {
        return $this->state(fn (array $attributes) => [
            'servings' => 1,
            'price_per_serving' => $this->faker->numberBetween(200, 350),
            'min_order' => 1,
        ]);
    }
}
