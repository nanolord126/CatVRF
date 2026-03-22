<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\PortfolioItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PortfolioItem>
 */
final class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'master_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'image_url' => $this->faker->imageUrl(800, 600, 'beauty', true),
            'before_image_url' => $this->faker->optional()->imageUrl(800, 600, 'before', true),
            'after_image_url' => $this->faker->optional()->imageUrl(800, 600, 'after', true),
            'tags' => [
                $this->faker->randomElement(['haircut', 'coloring', 'manicure', 'makeup', 'styling']),
            ],
        ];
    }
}
