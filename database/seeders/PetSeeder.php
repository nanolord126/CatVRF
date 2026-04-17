<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Pet\Models\PetAppointment;
use App\Domains\Pet\Models\PetBoarding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PetSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Pet vertical...');

            for ($i = 1; $i <= 20; $i++) {
                PetAppointment::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'pet_name' => "Pet {$i}",
                    'owner_id' => rand(1, 10),
                    'service_type' => ['grooming', 'veterinary', 'training'][rand(0, 2)],
                    'scheduled_at' => now()->addDays(rand(1, 30)),
                    'price' => rand(1000, 10000),
                    'status' => 'scheduled',
                ]);

                PetBoarding::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'pet_name' => "Pet {$i}",
                    'owner_id' => rand(1, 10),
                    'check_in_date' => now()->addDays(rand(1, 30)),
                    'check_out_date' => now()->addDays(rand(31, 60)),
                    'price_per_day' => rand(500, 5000),
                    'status' => 'active',
                ]);
            }

            $this->command->info('Pet vertical seeded successfully.');
        });
    }
}
