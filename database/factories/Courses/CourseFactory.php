<?php

declare(strict_types=1);

namespace Database\Factories\Courses;

use App\Domains\Education\Courses\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'instructor_id' => 1,
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(100000, 500000),
            'duration_hours' => fake()->numberBetween(10, 100),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'is_published' => true,
            'correlation_id' => Str::uuid()->toString(),
        ];
    }
}
