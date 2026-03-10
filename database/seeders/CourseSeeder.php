<?php

namespace Database\Seeders;

use App\Models\Domains\Education\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            ['title' => 'Laravel Advanced', 'duration_hours' => 40, 'price' => 500, 'status' => 'published'],
            ['title' => 'PHP Basics', 'duration_hours' => 20, 'price' => 200, 'status' => 'published'],
            ['title' => 'Database Design', 'duration_hours' => 30, 'price' => 350, 'status' => 'draft'],
        ];

        foreach ($courses as $course) {
            Course::factory()->create($course);
        }
    }
}
