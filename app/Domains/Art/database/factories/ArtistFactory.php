<?php
declare(strict_types=1);

namespace App\Domains\Art\database\factories;

use App\Domains\Art\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => $this->resolveTenantId(),
            'business_group_id' => null,
            'name' => $this->faker->name(),
            'slug' => Str::slug($this->faker->unique()->sentence(2)),
            'bio' => $this->faker->paragraph(),
            'style' => $this->faker->randomElement(['сюрреализм', 'арт-деко', 'минимализм', 'реализм']),
            'rating' => $this->faker->randomFloat(2, 4.2, 5.0),
            'is_active' => true,
            'tags' => ['primary' => 'art', 'vertical' => 'art'],
            'meta' => ['origin' => 'factory'],
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false, 'rating' => 0]);
    }

    public function withBusinessGroup(int $businessGroupId): self
    {
        return $this->state(fn () => ['business_group_id' => $businessGroupId]);
    }

    public function topRated(): self
    {
        return $this->state(fn () => ['rating' => 5]);
    }

    public function withTags(array $tags): self
    {
        return $this->state(fn () => ['tags' => $tags]);
    }

    public function withMeta(array $meta): self
    {
        return $this->state(fn () => ['meta' => $meta]);
    }

    public function forTenant(int $tenantId, ?int $businessGroupId = null): self
    {
        return $this->state(fn () => [
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
        ]);
    }

    public function withCorrelation(string $correlationId): self
    {
        return $this->state(fn () => ['correlation_id' => $correlationId]);
    }

    private function resolveTenantId(): int
    {
        if (function_exists('tenant') && tenant()) {
            return (int) tenant()->id;
        }

        return 1;
    }
}
