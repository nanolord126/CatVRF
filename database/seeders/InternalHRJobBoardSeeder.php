<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HRJobVacancy;
use App\Models\HRResume;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Внутренняя доска объявлений HR (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class InternalHRJobBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Vacancies
        $vacancies = [
            [
                'title' => 'Senior Logistic Dispatcher (Taxi AI)',
                'description' => 'Manage high-load taxi fleet using our vector-based logistics engine.',
                'skills' => ['Logistics', 'AI Operations', 'Fleet Management'],
                'salary_min' => 4500,
                'salary_max' => 6000,
                'vertical' => 'Taxi',
                'location_name' => 'Downtown Business Center',
                'status' => 'open',
            ],
            [
                'title' => 'Vet Clinic Administrator',
                'description' => 'Oversee medical scheduling and B2B supplies for the vet network.',
                'skills' => ['Administration', 'Healthcare', 'B2B Procurement'],
                'salary_min' => 2500,
                'salary_max' => 3800,
                'vertical' => 'Clinics',
                'location_name' => 'PetCare Hub North',
                'status' => 'open',
            ],
            [
                'title' => 'Food Operations Manager',
                'description' => 'Coordinate delivery zones and restaurant supplies.',
                'skills' => ['Supply Chain', 'Customer Success', 'Inventory Control'],
                'salary_min' => 3000,
                'salary_max' => 4500,
                'vertical' => 'Food',
                'location_name' => 'Main Gastro Hub',
                'status' => 'open',
            ],
        ];

        foreach ($vacancies as $data) {
            HRJobVacancy::updateOrCreate(['title' => $data['title']], $data);
        }

        // 2. Create Candidate Resumes for existing Users
        $users = User::limit(10)->get();

        $skillPool = [
            ['PHP', 'Laravel', 'AI Operations'],
            ['Logistics', 'Customer Success', 'Planning'],
            ['Healthcare', 'Nursing', 'Administration'],
            ['Marketing', 'Procurement', 'B2B Sales']
        ];

        foreach ($users as $index => $user) {
            HRResume::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'skills' => $skillPool[$index % 4],
                    'experience_history' => [
                        ['company' => 'EcoCorp 2025', 'role' => 'Junior Staff', 'years' => 2],
                        ['company' => 'Self-employed', 'role' => 'Freelancer', 'years' => 1],
                    ],
                    'ai_talent_score' => 0.6 + (rand(0, 40) / 100), // Random score between 0.6 and 1.0
                    'correlation_id' => (string) Str::uuid(),
                ]
            );
        }
    }
}
