<?php
declare(strict_types=1);

namespace App\Domains\Art\database\factories;

use App\Domains\Art\Models\Artist;
use App\Domains\Art\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $artist = Artist::factory()->create();

        return [
            'uuid' => (string) Str::uuid(),
            'correlation_id' => (string) Str::uuid(),
            'tenant_id' => $artist->tenant_id,
            'business_group_id' => $artist->business_group_id,
            'artist_id' => $artist->id,
            'title' => $this->faker->sentence(3),
            'brief' => $this->faker->paragraph(),
            'budget_cents' => $this->faker->numberBetween(10_000, 100_000),
            'status' => 'active',
            'mode' => $this->faker->randomElement(['b2c', 'b2b']),
            'deadline_at' => Carbon::now()->addWeeks(3),
            'preferences' => ['color' => 'warm'],
            'tags' => ['vertical' => 'art'],
            'meta' => ['factory' => true],
        ];
    }

    public function b2b(): self
    {
        return $this->state(fn () => ['mode' => 'b2b', 'budget_cents' => 150_000]);
    }

    public function draft(): self
    {
        return $this->state(fn () => ['status' => 'draft']);
    }

    public function cancelled(): self
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function urgent(): self
    {
        return $this->state(fn () => ['deadline_at' => Carbon::now()->addDays(5), 'status' => 'active']);
    }

    public function enterprise(): self
    {
        return $this->state(fn () => [
            'mode' => 'b2b',
            'budget_cents' => 300_000,
            'preferences' => ['support' => 'dedicated', 'nda' => true],
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

    public function forTenant(int $tenantId, ?int $businessGroupId = null): self
    {
        return $this->state(fn () => [
            'tenant_id' => $tenantId,
            'business_group_id' => $businessGroupId,
        ]);
    }
}
