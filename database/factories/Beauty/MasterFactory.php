<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\Master;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Master>
 */
final class MasterFactory extends Factory
{
    protected $model = Master::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'salon_id' => null, // Will be set externally
            'user_id' => 1, // Default user
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'full_name' => $this->faker->name(),
            'specialization' => [
                $this->faker->randomElement(['Hairdresser', 'Manicurist', 'Massage Therapist', 'Cosmetologist', 'Makeup Artist']),
                $this->faker->randomElement(['Color Specialist', 'Nail Art', 'Spa Specialist']),
            ],
            'experience_years' => $this->faker->numberBetween(1, 20),
            'rating' => $this->faker->randomFloat(2, 4.0, 5.0),
            'review_count' => $this->faker->numberBetween(10, 500),
            'is_active' => $this->faker->boolean(90),
            'tags' => [
                $this->faker->randomElement(['certified', 'top_rated', 'newcomer']),
            ],
        ];
    }

    public function topRated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 4.8,
            'review_count' => $this->faker->numberBetween(100, 500),
            'tags' => ['top_rated', 'certified'],
        ]);
    }
}
