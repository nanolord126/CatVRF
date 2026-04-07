<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\Supermarket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Супермаркеты (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class SupermarketSeeder extends Seeder
{
    public function run(): void
    {
        Supermarket::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
} 			'name' => 'City Market', 			'address' => '111 Market Street, Central District', 			'phone' => '+1-555-0501', 			'email' => 'info@citymarket.com', 			'geo_lat' => 40.7128, 			'geo_lng' => -74.0060, 			'opening_hours' => json_encode(['monday_saturday' => '07:00-23:00', 'sunday' => '08:00-22:00']), 			'status' => 'active', 		]);  		Supermarket::create([ 			'name' => 'Fresh Foods Supermarket', 			'address' => '222 Fresh Lane, Residential Area', 			'phone' => '+1-555-0502', 			'email' => 'contact@freshfoods.com', 			'geo_lat' => 40.7489, 			'geo_lng' => -73.9680, 			'opening_hours' => json_encode(['daily' => '06:00-24:00']), 			'status' => 'active', 		]);  		Supermarket::create([ 			'name' => 'Budget Mart Discount', 			'address' => '333 Value Road, Commercial Zone', 			'phone' => '+1-555-0503', 			'email' => 'savings@budgetmart.com', 			'geo_lat' => 40.7614, 			'geo_lng' => -73.9776, 			'opening_hours' => json_encode(['monday_friday' => '08:00-21:00', 'weekends' => '09:00-20:00']), 			'status' => 'active', 		]); 	} }
