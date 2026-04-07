<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\TaxiVehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Машины такси (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class TaxiVehicleSeeder extends Seeder
{
    public function run(): void
    {
        TaxiVehicle::factory()
            ->count(5)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
}             [                 'license_plate' => 'А001КЕ77',                 'model' => 'Yandex.Taxi Lada',                 'year' => 2022,                 'color' => 'White',                 'seats' => 4,                 'driver_id' => 1,                 'registration_date' => Carbon::now()->subYears(2),                 'insurance_expiry' => Carbon::now()->addMonths(6),                 'last_service_date' => Carbon::now()->subMonths(2),                 'mileage' => 45000,                 'vehicle_type' => 'economy',                 'is_available' => true,                 'features' => ['AC', 'USB charging', 'WiFi'],                 'status' => 'active',             ],             [                 'license_plate' => 'А002КЕ77',                 'model' => 'Toyota Camry',                 'year' => 2021,                 'color' => 'Black',                 'seats' => 4,                 'driver_id' => 2,                 'registration_date' => Carbon::now()->subYears(3),                 'insurance_expiry' => Carbon::now()->addMonths(8),                 'last_service_date' => Carbon::now()->subDays(30),                 'mileage' => 62000,                 'vehicle_type' => 'comfort',                 'is_available' => true,                 'features' => ['Premium seats', 'AC', 'USB', 'WiFi', 'Water'],                 'status' => 'active',             ],             [                 'license_plate' => 'А003КЕ77',                 'model' => 'Mercedes S-Class',                 'year' => 2023,                 'color' => 'Silver',                 'seats' => 4,                 'driver_id' => 3,                 'registration_date' => Carbon::now()->subYear(),                 'insurance_expiry' => Carbon::now()->addYears(1),                 'last_service_date' => Carbon::now()->subDays(15),                 'mileage' => 28000,                 'vehicle_type' => 'premium',                 'is_available' => false,                 'features' => ['Leather seats', 'Panorama roof', 'Heated seats', 'WiFi', 'Massage seats'],                 'status' => 'active',             ],             [                 'license_plate' => 'А004КЕ77',                 'model' => 'Ford Transit',                 'year' => 2020,                 'color' => 'White',                 'seats' => 8,                 'driver_id' => 4,                 'registration_date' => Carbon::now()->subYears(4),                 'insurance_expiry' => Carbon::now()->addMonths(3),                 'last_service_date' => Carbon::now()->subDays(45),                 'mileage' => 89000,                 'vehicle_type' => 'van',                 'is_available' => true,                 'features' => ['Large luggage', 'AC', 'USB', 'Multiple seats'],                 'status' => 'active',             ],             [                 'license_plate' => 'А005КЕ77',                 'model' => 'BMW 5 Series',                 'year' => 2022,                 'color' => 'Dark Blue',                 'seats' => 4,                 'driver_id' => 5,                 'registration_date' => Carbon::now()->subYears(2),                 'insurance_expiry' => Carbon::now()->addMonths(10),                 'last_service_date' => Carbon::now()->subDays(20),                 'mileage' => 38000,                 'vehicle_type' => 'premium',                 'is_available' => true,                 'features' => ['Leather', 'Sunroof', 'Heated seats', 'WiFi', 'Premium sound'],                 'status' => 'active',             ],         ];          foreach ($vehicles as $vehicle) {             TaxiVehicle::create($vehicle);         }     } }
