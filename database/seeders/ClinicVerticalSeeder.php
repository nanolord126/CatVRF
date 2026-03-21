<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use Illuminate\Support\Str;

/**
 * Медицинская вертикаль (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class ClinicVerticalSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создание или обновление тенантов для Медицины и Ветклиник
        $tenants = [
            [
                'id' => 'city-health',
                'name' => 'City Health Medical Center',
                'type' => 'clinic',
                'domain' => 'health.localhost',
            ],
            [
                'id' => 'pet-care-vet',
                'name' => 'PetCare Veterinary Clinic',
                'type' => 'vet',
                'domain' => 'vet.localhost',
            ],
        ];

        foreach ($tenants as $tData) {
            $tenant = Tenant::find($tData['id']);
            if (!$tenant) {
                $tenant = Tenant::create([
                    'id' => $tData['id'],
                    'name' => $tData['name'],
                    'type' => $tData['type'],
                ]);
                $tenant->domains()->create(['domain' => $tData['domain']]);
            }

            tenancy()->initialize($tenant);

            // 2. Создание Врачей/Персонала
            $doctorEmail = "doctor@{$tData['id']}.local";
            $doctor = User::where('email', $doctorEmail)->first();
            if (!$doctor) {
                $doctor = User::create([
                    'name' => $tData['type'] === 'clinic' ? 'Dr. Gregory House' : 'Dr. Dolittle',
                    'email' => $doctorEmail,
                    'password' => bcrypt('password'),
                ]);
            }

            // 3. Записи на прием (marketplace_verticals -> medical_appointments)
            if (Schema::hasTable('medical_appointments')) {
                // Если таблица пуста, добавим примеры
                if (DB::table('medical_appointments')->count() === 0) {
                    DB::table('medical_appointments')->insert([
                        [
                            'entity_type' => $tData['type'] === 'clinic' ? 'HUMAN' : 'ANIMAL',
                            'doctor_id' => $doctor->id,
                            'patient_name' => $tData['type'] === 'clinic' ? 'John Doe' : 'Rex the Dog',
                            'scheduled_at' => now()->addDays(1)->setHour(10)->setMinute(0),
                            'notes' => 'Primary consultation',
                            'status' => 'scheduled',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'entity_type' => $tData['type'] === 'clinic' ? 'HUMAN' : 'ANIMAL',
                            'doctor_id' => $doctor->id,
                            'patient_name' => $tData['type'] === 'clinic' ? 'Jane Smith' : 'Snowball the Cat',
                            'scheduled_at' => now()->addDays(2)->setHour(14)->setMinute(30),
                            'notes' => 'Follow-up visit',
                            'status' => 'scheduled',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    ]);
                }
            }

            // 4. Клиническая инфраструктура (clinics_vertical_tables если есть)
            if (Schema::hasTable('clinic_rooms')) {
                 DB::table('clinic_rooms')->insertOrIgnore([
                    ['name' => 'Room 101', 'type' => 'consultation'],
                    ['name' => 'Surgery A', 'type' => 'operating'],
                 ]);
            }

            tenancy()->end();
        }

        $this->command->info('ClinicVerticalSeeder: Medical and Vet clinics seeded successfully.');
    }
}
