<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Luxury\Jewelry\Models\JewelryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

final class JewelryItemFactory extends Factory
{
    protected $model = JewelryItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => $this->faker->uuid(),
            'correlation_id' => $this->faker->uuid(),
            'name' => $this->faker->word(),
            'metal' => $this->faker->randomElement(['gold', 'silver', 'platinum']),
            'stone' => $this->faker->randomElement(['diamond', 'sapphire', 'ruby']),
            'weight' => $this->faker->randomFloat(2, 1, 100),
            'price' => $this->faker->numberBetween(100000, 1000000),
            'certificate' => $this->faker->word(),
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'stock' => $this->faker->numberBetween(1, 50),
            'tags' => json_encode(['jewelry', 'luxury']),
        ];
    }
}
