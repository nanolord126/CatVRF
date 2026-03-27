<?php declare(strict_types=1);

namespace Database\Factories\Jewelry;

use App\Domains\Luxury\Jewelry\Models\Jewelry3DModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Jewelry3DModel>
 */
final class Jewelry3DModelFactory extends Factory
{
    protected $model = Jewelry3DModel::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'correlation_id' => Str::uuid(),
            'tenant_id' => 1,
            'jewelry_item_id' => 1,
            'model_url' => 'https://example.com/models/ring.glb',
            'texture_url' => 'https://example.com/textures/gold.png',
            'material_type' => fake()->randomElement(['gold', 'silver', 'platinum', 'rose_gold']),
            'dimensions' => [
                'width' => fake()->numberBetween(10, 50),
                'height' => fake()->numberBetween(10, 50),
                'depth' => fake()->numberBetween(5, 30),
            ],
            'weight_grams' => fake()->randomFloat(2, 1, 100),
            'preview_image_url' => 'https://example.com/preview/ring.jpg',
            'ar_compatible' => true,
            'vr_compatible' => true,
            'file_size_mb' => fake()->randomFloat(2, 0.5, 50),
            'format' => fake()->randomElement(['glb', 'gltf', 'usdz']),
            'status' => 'active',
            'tags' => ['jewelry', 'ring', '3d'],
        ];
    }
}
