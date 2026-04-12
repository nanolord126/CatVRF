<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmSegment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика CrmSegment — сегменты CRM-клиентов.
 * Канон CatVRF 2026.
 */
final class CrmSegmentFactory extends Factory
{
    protected $model = CrmSegment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'tags' => [],
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'vertical' => $this->faker->randomElement(['beauty', 'auto', 'food', 'hotel', 'taxi']),
            'is_dynamic' => $this->faker->boolean(70),
            'rules' => [
                ['field' => 'total_spent', 'operator' => '>=', 'value' => 1000],
            ],
            'clients_count' => $this->faker->numberBetween(0, 500),
            'last_calculated_at' => $this->faker->optional(0.5)->dateTimeBetween('-7 days', 'now'),
            'is_active' => true,
        ];
    }

    public function dynamic(): static
    {
        return $this->state(fn () => ['is_dynamic' => true]);
    }

    public function static(): static
    {
        return $this->state(fn () => ['is_dynamic' => false, 'rules' => []]);
    }
}
