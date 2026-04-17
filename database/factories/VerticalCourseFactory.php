<?php

namespace Database\Factories;

use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\VerticalCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

class VerticalCourseFactory extends Factory
{
    protected $model = VerticalCourse::class;

    public function definition(): array
    {
        $course = Course::factory()->create();
        
        return [
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => 1,
            'course_id' => $course->id,
            'vertical' => $this->faker->randomElement(['beauty', 'hotels', 'flowers', 'auto', 'medical', 'fitness', 'restaurants', 'pharmacy']),
            'target_role' => $this->faker->randomElement(['manager', 'specialist', 'administrator', 'receptionist']),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'duration_hours' => $this->faker->numberBetween(1, 100),
            'is_required' => $this->faker->boolean(30),
            'prerequisites' => null,
            'learning_objectives' => null,
            'metadata' => null,
            'correlation_id' => \Illuminate\Support\Str::uuid(),
        ];
    }

    public function forVertical(string $vertical): self
    {
        return $this->state(fn (array $attributes) => [
            'vertical' => $vertical,
        ]);
    }

    public function required(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    public function optional(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    public function beginner(): self
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'beginner',
        ]);
    }

    public function intermediate(): self
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'intermediate',
        ]);
    }

    public function advanced(): self
    {
        return $this->state(fn (array $attributes) => [
            'difficulty_level' => 'advanced',
        ]);
    }
}
