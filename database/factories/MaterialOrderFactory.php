<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\ConstructionAndRepair\ConstructionAndRepair\ConstructionMaterials\Models\MaterialOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class MaterialOrderFactory extends Factory
{
    protected $model = MaterialOrder::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid(),
            'correlation_id' => Str::uuid(),
            'material_id' => 1,
            'user_id' => 1,
            'quantity' => $this->faker->numberBetween(1, 50),
            'unit_price' => $this->faker->numberBetween(5000, 50000),
            'total_price' => $this->faker->numberBetween(5000, 500000),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'shipped', 'delivered']),
            'delivery_address' => $this->faker->address(),
            'tracking_number' => $this->faker->optional()->bothify('??-########'),
            'delivery_date' => $this->faker->optional()->dateTime(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'delivery_date' => now(),
            'tracking_number' => $this->faker->bothify('??-########'),
        ]);
    }
}
