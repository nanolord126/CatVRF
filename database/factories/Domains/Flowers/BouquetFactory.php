<?php declare(strict_types=1);

namespace Database\Factories\Domains\Flowers;

use App\Domains\Flowers\Models\Bouquet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class BouquetFactory extends Factory
{
    protected $model = Bouquet::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'shop_id' => 1,
            'uuid' => Str::uuid(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'images' => [fake()->imageUrl()],
            'flowers_composition' => [
                'roses' => fake()->numberBetween(5, 15),
                'tulips' => fake()->numberBetween(3, 10),
            ],
            'price' => fake()->randomFloat(2, 500, 5000),
            'consumables_json' => [
                'ribbon' => 1,
                'wrapping' => 1,
            ],
            'is_available' => true,
            'correlation_id' => Str::uuid(),
            'tags' => ['popular', 'romantic'],
        ];
    }
}
