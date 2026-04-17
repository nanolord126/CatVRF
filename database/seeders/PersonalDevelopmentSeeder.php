<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\PersonalDevelopment\Models\PersonalDevelopmentCourse;
use App\Domains\PersonalDevelopment\Models\PersonalDevelopmentCoach;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PersonalDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Personal Development vertical...');

            for ($i = 1; $i <= 20; $i++) {
                PersonalDevelopmentCourse::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Course {$i}",
                    'description' => "Description for course {$i}",
                    'category' => ['leadership', 'productivity', 'communication'][rand(0, 2)],
                    'price' => rand(3000, 50000),
                    'duration_hours' => rand(5, 50),
                    'status' => 'active',
                ]);

                PersonalDevelopmentCoach::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Coach {$i}",
                    'specialization' => ['leadership', 'productivity', 'communication'][rand(0, 2)],
                    'hourly_rate' => rand(2000, 20000),
                    'status' => 'available',
                ]);
            }

            $this->command->info('Personal Development vertical seeded successfully.');
        });
    }
}
