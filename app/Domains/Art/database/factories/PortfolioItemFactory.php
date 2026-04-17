<?php
declare(strict_types=1);

namespace App\Domains\Art\database\factories;

use App\Domains\Art\Models\PortfolioItem;
use App\Domains\Art\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class PortfolioItemFactory extends Factory
{
    protected $model = PortfolioItem::class;

    public function definition(): array
    {
        $project = Project::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => $project->tenant_id,
            'business_group_id' => $project->business_group_id,
            'artist_id' => $project->artist_id,
            'project_id' => $project->id,
            'title' => $this->faker->sentence(3),
            'cover_url' => $this->faker->imageUrl(),
            'description' => $this->faker->paragraph(),
            'published_at' => Carbon::now(),
            'tags' => ['type' => 'portfolio'],
            'meta' => ['source' => 'factory'],
        ];
    }

    public function unpublished(): self
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function withoutProject(): self
    {
        return $this->state(fn () => ['project_id' => null]);
    }

    public function forTenant(int $tenantId, ?int $businessGroupId = null): self
    {
        return $this->state(fn () => [
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
        ]);
    }

    public function withTags(array $tags): self
    {
        return $this->state(fn () => ['tags' => $tags]);
    }

    public function withCorrelation(string $correlationId): self
    {
        return $this->state(fn () => ['correlation_id' => $correlationId]);
    }

    public function withMeta(array $meta): self
    {
        return $this->state(fn () => ['meta' => $meta]);
    }

    public function publishedAt(
        ?Carbon $publishedAt
    ): self {
        return $this->state(fn () => ['published_at' => $publishedAt]);
    }

    public function withCoverUrl(string $coverUrl): self
    {
        return $this->state(fn () => ['cover_url' => $coverUrl]);
    }
}
