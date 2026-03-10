<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Analytics\ConsumerBehaviorLog;
use Illuminate\Support\Str;

class ConsumerBehaviorSeeder extends Seeder
{
    /**
     * Seed example user behavior for better AI dashboard visualization.
     */
    public function run(): void
    {
        $users = User::all();
        $eventTypes = ['purchase', 'view_product', 'search', 'taxi_cancel', 'appointment_book'];

        foreach ($users as $user) {
            // Seed 10-20 random events per user
            for ($i = 0; $i < rand(10, 20); $i++) {
                $type = $eventTypes[array_rand($eventTypes)];
                $amount = ($type === 'purchase') ? rand(500, 5000) : 0;

                ConsumerBehaviorLog::create([
                    'user_id' => $user->id,
                    'event_type' => $type,
                    'entity_type' => ($type === 'purchase') ? 'App\Models\Food\Order' : 'App\Models\Taxi\Ride',
                    'entity_id' => rand(1, 100),
                    'payload' => [
                        'amount' => $amount,
                        'source' => 'mobile_app',
                        'location' => 'Moscow, RU'
                    ],
                    'correlation_id' => (string) Str::uuid(),
                    'created_at' => now()->subDays(rand(0, 365)),
                ]);
            }
        }
    }
}
