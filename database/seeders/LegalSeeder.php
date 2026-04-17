<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Legal\Models\LegalCase;
use App\Domains\Legal\Models\LegalConsultation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LegalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Legal vertical...');

            for ($i = 1; $i <= 20; $i++) {
                LegalCase::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'case_number' => "CASE-{$i}",
                    'title' => "Legal Case {$i}",
                    'description' => "Description for legal case {$i}",
                    'type' => ['civil', 'criminal', 'corporate', 'family'][rand(0, 3)],
                    'status' => ['open', 'in_progress', 'closed'][rand(0, 2)],
                    'client_id' => rand(1, 10),
                    'lawyer_id' => rand(1, 5),
                ]);

                LegalConsultation::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'title' => "Consultation {$i}",
                    'description' => "Description for consultation {$i}",
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'duration_minutes' => rand(30, 120),
                    'price' => rand(5000, 50000),
                    'status' => 'scheduled',
                    'client_id' => rand(1, 10),
                ]);
            }

            $this->command->info('Legal vertical seeded successfully.');
        });
    }
}
