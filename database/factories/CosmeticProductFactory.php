<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Beauty\Cosmetics\Models\CosmeticProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CosmeticProductFactory extends Factory
{
    protected $model = CosmeticProduct::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => $this->faker->uuid(),
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'brand' => $this->faker->word(),
            'category' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(5000, 50000),
            'ingredients' => json_encode(['water', 'oil']),
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'stock' => $this->faker->numberBetween(10, 500),
            'tags' => json_encode(['cosmetics', 'beauty']),
        ];
    }
}
