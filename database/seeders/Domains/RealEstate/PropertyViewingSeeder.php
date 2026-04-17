<?php

declare(strict_types=1);

namespace Database\Seeders\Domains\RealEstate;

use Illuminate\Database\Seeder;
use App\Domains\RealEstate\Models\PropertyViewing;
use App\Domains\RealEstate\Models\Property;
use App\Models\Domains\RealEstate\RealEstateAgent;
use App\Models\User;
use App\Models\Tenant;
use Carbon\Carbon;

final class PropertyViewingSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::factory()->create();
        }

        $properties = Property::where('tenant_id', $tenant->id)->get();
        if ($properties->isEmpty()) {
            $properties = Property::factory()->count(10)->create(['tenant_id' => $tenant->id]);
        }

        $users = User::take(20)->get();
        if ($users->isEmpty()) {
            $users = User::factory()->count(20)->create();
        }

        $agents = RealEstateAgent::where('tenant_id', $tenant->id)->get();
        if ($agents->isEmpty()) {
            $agents = RealEstateAgent::factory()->count(5)->create(['tenant_id' => $tenant->id]);
        }

        $statuses = ['pending', 'held', 'confirmed', 'completed', 'cancelled', 'no_show'];

        foreach ($properties as $property) {
            $viewingsCount = rand(3, 15);

            for ($i = 0; $i < $viewingsCount; $i++) {
                $user = $users->random();
                $agent = $agents->random();
                $status = $statuses[array_rand($statuses)];
                $isB2B = (bool) rand(0, 1);

                $scheduledAt = Carbon::now()->addDays(rand(1, 30))->setHour(rand(9, 18))->setMinute(0);

                $viewing = PropertyViewing::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'business_group_id' => null,
                    'property_id' => $property->id,
                    'user_id' => $user->id,
                    'agent_id' => $agent->id,
                    'scheduled_at' => $scheduledAt,
                    'held_at' => $status === 'held' ? Carbon::now()->subMinutes(rand(1, 14)) : null,
                    'hold_expires_at' => $status === 'held' ? Carbon::now()->addMinutes(rand(1, 14)) : null,
                    'completed_at' => $status === 'completed' ? $scheduledAt->copy()->addHours(rand(1, 2)) : null,
                    'cancelled_at' => in_array($status, ['cancelled', 'no_show']) ? $scheduledAt->copy()->subHours(rand(1, 24)) : null,
                    'status' => $status,
                    'is_b2b' => $isB2B,
                    'webrtc_room_id' => 'room_' . md5($property->id . $user->id . $scheduledAt->toIso8601String()),
                    'faceid_verified' => (bool) rand(0, 1),
                    'cancellation_reason' => in_array($status, ['cancelled', 'no_show']) ? ['client_cancelled', 'agent_cancelled', 'no_show'][rand(0, 2)] : null,
                    'correlation_id' => \Illuminate\Support\Str::uuid(),
                    'metadata' => [
                        'preferred_contact_method' => ['phone', 'email', 'wechat', 'telegram'][rand(0, 3)],
                        'number_of_attendees' => rand(1, 4),
                        'special_requirements' => rand(0, 1) ? 'Need wheelchair access' : null,
                    ],
                    'tags' => [
                        'priority_' . ['low', 'medium', 'high'][rand(0, 2)],
                        $isB2B ? 'b2b' : 'b2c',
                    ],
                ]);

                $this->command->info("Created viewing: {$viewing->uuid} for property {$property->id}");
            }
        }

        $this->command->info('PropertyViewings seeded successfully.');
    }
}
