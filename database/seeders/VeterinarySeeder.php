<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Veterinary\Models\VeterinaryAppointment;
use App\Domains\Veterinary\Models\VeterinaryPatient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class VeterinarySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Veterinary vertical...');

            for ($i = 1; $i <= 20; $i++) {
                VeterinaryPatient::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Pet {$i}",
                    'species' => ['dog', 'cat', 'bird'][rand(0, 2)],
                    'breed' => "Breed {$i}",
                    'age_years' => rand(1, 15),
                    'owner_id' => rand(1, 10),
                ]);

                VeterinaryAppointment::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'patient_id' => $i,
                    'vet_id' => rand(1, 5),
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'reason' => "Checkup {$i}",
                    'price' => rand(1000, 10000),
                    'status' => 'scheduled',
                ]);
            }

            $this->command->info('Veterinary vertical seeded successfully.');
        });
    }
}
