<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\EventPlanning\Models\EventPlanningEvent;
use App\Domains\EventPlanning\Models\EventPlanningBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EventPlanningSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Event Planning vertical...');

            for ($i = 1; $i <= 20; $i++) {
                EventPlanningEvent::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Event {$i}",
                    'description' => "Description for event {$i}",
                    'type' => ['wedding', 'corporate', 'birthday'][rand(0, 2)],
                    'venue' => "Venue {$i}",
                    'capacity' => rand(50, 500),
                    'price_per_person' => rand(2000, 20000),
                    'status' => 'available',
                ]);

                EventPlanningBooking::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'event_id' => $i,
                    'client_id' => rand(1, 10),
                    'event_date' => now()->addDays(rand(1, 90)),
                    'guests' => rand(20, 200),
                    'total_price' => rand(50000, 1000000),
                    'status' => ['confirmed', 'pending', 'completed'][rand(0, 2)],
                ]);
            }

            $this->command->info('Event Planning vertical seeded successfully.');
        });
    }
}
