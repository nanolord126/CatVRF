<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Tickets\Models\TicketsBooking;
use App\Domains\Tickets\Models\TicketsEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TicketsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding Tickets vertical...');

            for ($i = 1; $i <= 20; $i++) {
                TicketsEvent::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'name' => "Event {$i}",
                    'description' => "Description for event {$i}",
                    'type' => ['concert', 'theater', 'sports'][rand(0, 2)],
                    'venue' => "Venue {$i}",
                    'event_date' => now()->addDays(rand(1, 90)),
                    'total_tickets' => rand(100, 1000),
                    'available_tickets' => rand(10, 500),
                    'price' => rand(1000, 20000),
                    'status' => 'available',
                ]);

                TicketsBooking::create([
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => 1,
                    'business_group_id' => 1,
                    'correlation_id' => Str::uuid()->toString(),
                    'event_id' => $i,
                    'user_id' => rand(1, 10),
                    'quantity' => rand(1, 10),
                    'total_price' => rand(1000, 50000),
                    'status' => ['confirmed', 'pending', 'cancelled'][rand(0, 2)],
                ]);
            }

            $this->command->info('Tickets vertical seeded successfully.');
        });
    }
}
