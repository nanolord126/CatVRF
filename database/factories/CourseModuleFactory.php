<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Education\Models\CourseModule;
use App\Domains\Education\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

final class CourseModuleFactory extends Factory
{
    protected $model = CourseModule::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'order' => $this->faker->numberBetween(1, 10),
            'estimated_hours' => $this->faker->numberBetween(5, 20),
            'difficulty' => $this->faker->randomElement(['beginner', 'easy', 'medium', 'hard', 'expert']),
            'prerequisites' => [],
            'learning_objectives' => $this->faker->sentences(3),
        ];
    }

    public function beginner(): self
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'beginner',
            'estimated_hours' => 5,
        ]);
    }

    public function advanced(): self
    {
        return $this->state(fn (array $attributes) => [
            'difficulty' => 'hard',
            'estimated_hours' => 20,
        ]);
    }
}
