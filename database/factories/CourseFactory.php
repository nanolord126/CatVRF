<?php

namespace Database\Factories;

use App\Models\Domains\Education\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'instructor_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'duration_hours' => $this->faker->numberBetween(10, 100),
            'price' => $this->faker->numberBetween(100, 10000),
            'max_students' => $this->faker->numberBetween(10, 100),
            'start_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ];
    }
}
