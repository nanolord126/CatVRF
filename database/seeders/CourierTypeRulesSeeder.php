<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CourierTypeRulesSeeder extends Seeder
{
    public function run(): void
    {
        $defaultRules = [
            [
                'type' => 'pedestrian',
                'max_radius_km' => 2.5,
                'max_weight_kg' => 10.0,
                'avg_speed_kmh' => 5.0,
                'max_delivery_time_min' => 45,
                'parking_time_min' => 0,
                'battery_threshold' => null,
                'requires_battery' => false,
                'cost_multiplier' => 1.000,
                'weather_penalties' => json_encode(['rain' => 0.3, 'snow' => 0.7, 'wind' => 0.1]),
                'operating_hours' => json_encode(['start' => '08:00', 'end' => '22:00']),
                'priority' => 10,
            ],
            [
                'type' => 'scooter',
                'max_radius_km' => 8.0,
                'max_weight_kg' => 15.0,
                'avg_speed_kmh' => 15.0,
                'max_delivery_time_min' => 35,
                'parking_time_min' => 2,
                'battery_threshold' => 30,
                'requires_battery' => true,
                'cost_multiplier' => 1.200,
                'weather_penalties' => json_encode(['rain' => 0.4, 'snow' => 0.8, 'wind' => 0.2]),
                'operating_hours' => json_encode(['start' => '07:00', 'end' => '23:00']),
                'priority' => 20,
            ],
            [
                'type' => 'ebike',
                'max_radius_km' => 10.0,
                'max_weight_kg' => 20.0,
                'avg_speed_kmh' => 20.0,
                'max_delivery_time_min' => 30,
                'parking_time_min' => 2,
                'battery_threshold' => 30,
                'requires_battery' => true,
                'cost_multiplier' => 1.300,
                'weather_penalties' => json_encode(['rain' => 0.3, 'snow' => 0.6, 'wind' => 0.15]),
                'operating_hours' => json_encode(['start' => '07:00', 'end' => '23:00']),
                'priority' => 15,
            ],
            [
                'type' => 'car',
                'max_radius_km' => 20.0,
                'max_weight_kg' => 100.0,
                'avg_speed_kmh' => 30.0,
                'max_delivery_time_min' => 90,
                'parking_time_min' => 8,
                'battery_threshold' => null,
                'requires_battery' => false,
                'cost_multiplier' => 2.000,
                'weather_penalties' => json_encode(['rain' => 0.1, 'snow' => 0.3, 'wind' => 0.05]),
                'operating_hours' => json_encode(['start' => '00:00', 'end' => '23:59']),
                'priority' => 50,
            ],
            [
                'type' => 'taxi',
                'max_radius_km' => 25.0,
                'max_weight_kg' => 100.0,
                'avg_speed_kmh' => 30.0,
                'max_delivery_time_min' => 90,
                'parking_time_min' => 5,
                'battery_threshold' => null,
                'requires_battery' => false,
                'cost_multiplier' => 2.200,
                'weather_penalties' => json_encode(['rain' => 0.1, 'snow' => 0.3, 'wind' => 0.05]),
                'operating_hours' => json_encode(['start' => '00:00', 'end' => '23:59']),
                'priority' => 60,
            ],
        ];

        $tenantsTableExists = Schema::hasTable('tenants');
        $tenantIds = [];

        if ($tenantsTableExists) {
            $tenantIds = DB::table('tenants')->pluck('id')->toArray();
        }

        if (empty($tenantIds)) {
            $this->command->warn('No tenants available. Skipping courier type rules seeding.');
            $this->command->info('Run the tenant seeder first or create tenants manually.');

            return;
        }

        foreach ($tenantIds as $tenantId) {
            foreach ($defaultRules as $rule) {
                DB::table('courier_type_rules')->insert(array_merge($rule, [
                    'tenant_id' => $tenantId,
                    'city' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $this->command->info('Courier type rules seeded successfully.');
    }
}