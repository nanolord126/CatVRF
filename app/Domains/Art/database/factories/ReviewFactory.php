<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Art\Models\Project;
use App\Domains\Art\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $project = Project::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => $project->tenant_id,
            'business_group_id' => $project->business_group_id,
            'project_id' => $project->id,
            'artist_id' => $project->artist_id,
            'user_id' => 1,
            'rating' => $this->faker->numberBetween(4, 5),
            'comment' => $this->faker->sentence(),
            'tags' => ['quality' => 'excellent'],
            'meta' => ['origin' => 'factory'],
        ];
    }

    public function negative(): self
    {
        return $this->state(fn () => ['rating' => 2, 'comment' => 'Нужно доработать']);
    }

    public function fromUser(int $userId): self
    {
        return $this->state(fn () => ['user_id' => $userId]);
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

    public function critical(): self
    {
        return $this->state(fn () => ['rating' => 1, 'comment' => 'Критические замечания']);
    }

    public function withTags(array $tags): self
    {
        return $this->state(fn () => ['tags' => $tags]);
    }

    public function withMeta(array $meta): self
    {
        return $this->state(fn () => ['meta' => $meta]);
    }

    public function verified(): self
    {
        return $this->state(fn () => ['meta' => ['verified' => true]]);
    }
}
