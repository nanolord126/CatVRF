<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Art\Models\Artwork;
use App\Domains\Art\Models\Artist;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ArtworkFactory extends Factory
{
    protected $model = Artwork::class;

    public function definition(): array
    {
        $artist = Artist::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => $artist->tenant_id,
            'business_group_id' => $artist->business_group_id,
            'artist_id' => $artist->id,
            'project_id' => null,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'price_cents' => $this->faker->numberBetween(5_000, 50_000),
            'is_visible' => true,
            'delivered_at' => null,
            'tags' => ['palette' => 'warm'],
            'meta' => ['source' => 'factory'],
        ];
    }

    public function delivered(): self
    {
        return $this->state(fn () => ['delivered_at' => Carbon::now()]);
    }

    public function hidden(): self
    {
        return $this->state(fn () => ['is_visible' => false]);
    }

    public function cheap(): self
    {
        return $this->state(fn () => ['price_cents' => 1500]);
    }

    public function premium(): self
    {
        return $this->state(fn () => ['price_cents' => 250_000]);
    }

    public function forProject(int $projectId): self
    {
        return $this->state(fn () => ['project_id' => $projectId]);
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
}
