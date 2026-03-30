<?php

declare(strict_types=1);

namespace Database\Factories\Domains\Beauty;

use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\PortfolioItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => Tenant::factory(),
            'master_id' => Master::factory(),
            'image_url' => $this->faker->imageUrl(640, 480, 'people', true),
            'description' => $this->faker->realText(150),
            'tags' => json_encode([$this->faker->word(), $this->faker->word()]),
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
