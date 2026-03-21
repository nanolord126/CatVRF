<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ToysKids\Models\ToysKids;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ToysKidsFactory extends Factory
{
    protected $model = ToysKids::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->randomElement([
                'Кубики деревянные',
                'Конструктор LEGO классический',
                'Плюшевый мишка',
                'Паззл 500 деталей',
                'Игрушечный набор машинок',
                'Робот трансформер',
                'Велосипед детский 20"',
                'Настольная игра Монополия',
            ]),
            'sku' => 'TOY-' . strtoupper($this->faker->lexify('???')),
            'category' => $this->faker->randomElement(['puzzle', 'plush', 'building', 'vehicle', 'board_game', 'outdoor']),
            'age_min' => $this->faker->numberBetween(1, 10),
            'age_max' => $this->faker->numberBetween(12, 18),
            'price' => $this->faker->numberBetween(50000, 300000),
            'current_stock' => $this->faker->numberBetween(5, 100),
            'rating' => $this->faker->randomFloat(1, 3.5, 5.0),
            'tags' => null,
        ];
    }

    public function premium(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(200000, 300000),
            'rating' => $this->faker->randomFloat(1, 4.5, 5.0),
        ]);
    }

    public function budget(): self
    {
        return $this->state(fn (array $attributes) => [
            'price' => $this->faker->numberBetween(50000, 120000),
            'rating' => $this->faker->randomFloat(1, 3.5, 4.5),
        ]);
    }
}
