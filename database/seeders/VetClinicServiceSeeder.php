<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenants\VetClinicService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Услуги ветеринарных клиник (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class VetClinicServiceSeeder extends Seeder
{
    public function run(): void
    {
        VetClinicService::factory()
            ->count(5)
            ->create(['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]);
    }
} 			'vet_clinic_id' => 1, 			'name' => 'Comprehensive Health Check-up', 			'description' => 'Full physical examination and health assessment for pets', 			'category' => 'consultation', 			'price' => 75.00, 			'duration_minutes' => 30, 			'requires_appointment' => true, 			'prerequisites' => json_encode(['appointment_required' => true]), 			'status' => 'active', 		]);  		VetClinicService::create([ 			'vet_clinic_id' => 1, 			'name' => 'Vaccination Service', 			'description' => 'Complete vaccination package including rabies, DHPP, and other essential vaccines', 			'category' => 'vaccination', 			'price' => 150.00, 			'duration_minutes' => 20, 			'requires_appointment' => true, 			'prerequisites' => json_encode(['documents' => ['vaccination_history']]), 			'status' => 'active', 		]);  		VetClinicService::create([ 			'vet_clinic_id' => 1, 			'name' => 'Surgical Procedure - Spaying/Neutering', 			'description' => 'Surgical spaying or neutering for dogs and cats', 			'category' => 'surgery', 			'price' => 500.00, 			'duration_minutes' => 60, 			'requires_appointment' => true, 			'prerequisites' => json_encode(['pre_operative_blood_work' => true]), 			'status' => 'active', 		]);  		VetClinicService::create([ 			'vet_clinic_id' => 1, 			'name' => 'Dental Cleaning', 			'description' => 'Professional dental cleaning and oral health assessment', 			'category' => 'dental', 			'price' => 200.00, 			'duration_minutes' => 45, 			'requires_appointment' => true, 			'prerequisites' => json_encode(['fasting_required' => true]), 			'status' => 'active', 		]);  		VetClinicService::create([ 			'vet_clinic_id' => 1, 			'name' => 'Microchip Implantation', 			'description' => 'Permanent identification through microchip implantation', 			'category' => 'identification', 			'price' => 50.00, 			'duration_minutes' => 10, 			'requires_appointment' => false, 			'status' => 'active', 		]); 	} }
