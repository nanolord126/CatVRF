<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Education\Models\EducationCourse;
use App\Domains\Education\Models\EducationStudent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EducationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Education vertical...');

            for ($i = 1; $i <= 20; $i++) {
                EducationCourse::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Course {$i}",
                    'description' => "Description for course {$i}",
                    'category' => ['programming', 'design', 'business', 'marketing'][rand(0, 3)],
                    'price' => rand(5000, 100000),
                    'duration_hours' => rand(10, 100),
                    'instructor_id' => rand(1, 5),
                    'status' => 'active',
                ]);

                EducationStudent::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'course_id' => $i,
                    'user_id' => rand(1, 10),
                    'enrollment_date' => now()->subDays(rand(1, 365)),
                    'progress' => rand(0, 100),
                    'status' => ['active', 'completed', 'dropped'][rand(0, 2)],
                ]);
            }

            $this->command->info('Education vertical seeded successfully.');
        });
    }
}
