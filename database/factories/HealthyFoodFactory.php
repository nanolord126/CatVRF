<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\HealthyFood\Models\HealthyFood;
use Illuminate\Database\Eloquent\Factories\Factory;

final class HealthyFoodFactory extends Factory
{
    protected $model = HealthyFood::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->randomElement([
                'Обед Здоровый Боул',
                'Салат Цезарь Органик',
                'Блюдо Кето-Вечеров',
                'Каша Гречка С Овощами',
                'Рис Коричневый Белки',
                'Паста Полезная',
                'Овощное ассорти Гриль',
                'Рыба Лосось Стейк',
            ]),
            'sku' => 'HF-' . strtoupper($this->faker->lexify('???')),
            'diet_type' => $this->faker->randomElement(['vegan', 'keto', 'protein', 'balanced', 'lowcarb']),
            'calories' => $this->faker->numberBetween(400, 800),
            'protein_g' => $this->faker->numberBetween(15, 40),
            'carbs_g' => $this->faker->numberBetween(20, 80),
            'fat_g' => $this->faker->numberBetween(10, 40),
            'price' => $this->faker->numberBetween(150000, 350000),
            'current_stock' => $this->faker->numberBetween(5, 100),
            'rating' => $this->faker->randomFloat(1, 4.0, 5.0),
            'tags' => null,
        ];
    }

    public function organic(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(250000, 350000),
            'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
        ]);
    }

    public function budget(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(150000, 200000),
            'rating' => $this->faker->randomFloat(1, 3.5, 4.5),
        ]);
    }
}
