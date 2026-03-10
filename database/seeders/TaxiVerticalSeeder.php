<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxiVerticalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создание или обновление тенанта для Такси-парка
        $tenantId = 'city-taxi-fleet';
        $domain = 'taxi.localhost';
        $companyName = 'City Taxi & Logistic';

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $companyName,
                'type' => 'taxi',
            ]);
            $tenant->domains()->create(['domain' => $domain]);
        }

        tenancy()->initialize($tenant);

        // 2. Создание водителя
        $driverEmail = 'driver@city-taxi.local';
        $driver = User::where('email', $driverEmail)->first();
        if (!$driver) {
            $driver = User::create([
                'name' => 'Michael Schumacher Jr.',
                'email' => $driverEmail,
                'password' => bcrypt('password'),
            ]);
        }

        // 3. Машины (если есть таблица taxi_vehicles, иначе через общую)
        if (Schema::hasTable('taxi_vehicles')) {
            DB::table('taxi_vehicles')->insertOrIgnore([
                [
                    'model' => 'Toyota Camry v2026',
                    'number' => 'A777AA777',
                    'driver_id' => $driver->id,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        // 4. Поездки (marketplace_verticals -> taxi_trips)
        if (Schema::hasTable('taxi_trips') && DB::table('taxi_trips')->count() === 0) {
            DB::table('taxi_trips')->insert([
                [
                    'driver_id' => $driver->id,
                    'from_address' => 'Central Station',
                    'to_address' => 'Elite Spa & Beauty Center',
                    'fare' => 1200.00,
                    'status' => 'completed',
                    'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'driver_id' => $driver->id,
                    'from_address' => 'Airport Gate A',
                    'to_address' => 'Grand Hotel Luxury',
                    'fare' => 3500.00,
                    'status' => 'active',
                    'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        // 5. Тарифы (full_taxi_vertical_tables если есть)
        if (Schema::hasTable('taxi_tariffs')) {
            DB::table('taxi_tariffs')->insertOrIgnore([
                ['name' => 'Economy', 'base_fare' => 100.00, 'per_km' => 20],
                ['name' => 'Premium', 'base_fare' => 500.00, 'per_km' => 80],
            ]);
        }

        tenancy()->end();

        $this->command->info('TaxiVerticalSeeder: Taxi fleet and trips seeded successfully.');
    }
}
