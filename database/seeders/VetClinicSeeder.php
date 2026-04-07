<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\VetClinic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Ветеринарные клиники (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class VetClinicSeeder extends Seeder
{
    public function run(): void
    {
        VetClinic::factory()
            ->count(3)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
} 			'name' => 'Happy Paws Veterinary', 			'address' => '234 Pet Avenue, Animal District', 			'phone' => '+1-555-0201', 			'email' => 'info@happypaws.com', 			'geo_lat' => 40.7489, 			'geo_lng' => -73.9680, 			'specialization' => json_encode(['dogs', 'cats', 'small_animals']), 			'opening_hours' => json_encode(['monday' => '09:00-18:00', 'saturday' => '10:00-16:00']), 			'status' => 'active', 		]);  		VetClinic::create([ 			'name' => 'Veterinary Excellence Center', 			'address' => '567 Animal Care Road, Pet Zone', 			'phone' => '+1-555-0202', 			'email' => 'contact@vetexcellence.com', 			'geo_lat' => 40.7614, 			'geo_lng' => -73.9776, 			'specialization' => json_encode(['dogs', 'cats', 'horses', 'exotic_pets']), 			'opening_hours' => json_encode(['monday_friday' => '08:00-20:00', 'weekends' => '09:00-17:00']), 			'status' => 'active', 		]);  		VetClinic::create([ 			'name' => 'Emergency Vet Clinic 24/7', 			'address' => '890 Emergency Pet Lane, Urgent Care Area', 			'phone' => '+1-555-0203', 			'email' => 'emergency@vetclinic24.com', 			'geo_lat' => 40.7505, 			'geo_lng' => -73.9972, 			'specialization' => json_encode(['emergency_care', 'surgery']), 			'opening_hours' => json_encode(['24_7' => 'open']), 			'status' => 'active', 		]); 	} }
