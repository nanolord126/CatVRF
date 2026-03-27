<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Medical\MedicalHealthcare\Models\MedicalAppointment;
use Illuminate\Database\Seeder;

final class MedicalAppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['clinic_id' => 1, 'doctor_id' => 1, 'patient_name' => 'Иван Петров', 'patient_phone' => '+7-900-111-1111', 'duration_minutes' => 30, 'status' => 'completed', 'price' => 150000],
            ['clinic_id' => 1, 'doctor_id' => 2, 'patient_name' => 'Мария Сидорова', 'patient_phone' => '+7-900-222-2222', 'duration_minutes' => 45, 'status' => 'completed', 'price' => 200000],
            ['clinic_id' => 1, 'doctor_id' => 1, 'patient_name' => 'Алексей Иванов', 'patient_phone' => '+7-900-333-3333', 'duration_minutes' => 30, 'status' => 'pending', 'price' => 150000],
            ['clinic_id' => 1, 'doctor_id' => 3, 'patient_name' => 'Светлана Петрова', 'patient_phone' => '+7-900-444-4444', 'duration_minutes' => 60, 'status' => 'confirmed', 'price' => 250000],
            ['clinic_id' => 1, 'doctor_id' => 2, 'patient_name' => 'Николай Смирнов', 'patient_phone' => '+7-900-555-5555', 'duration_minutes' => 30, 'status' => 'completed', 'price' => 180000],
            ['clinic_id' => 1, 'doctor_id' => 1, 'patient_name' => 'Ольга Козлова', 'patient_phone' => '+7-900-666-6666', 'duration_minutes' => 45, 'status' => 'pending', 'price' => 160000],
            ['clinic_id' => 1, 'doctor_id' => 3, 'patient_name' => 'Владимир Новиков', 'patient_phone' => '+7-900-777-7777', 'duration_minutes' => 60, 'status' => 'cancelled', 'price' => 240000],
            ['clinic_id' => 1, 'doctor_id' => 2, 'patient_name' => 'Надежда Волкова', 'patient_phone' => '+7-900-888-8888', 'duration_minutes' => 30, 'status' => 'completed', 'price' => 170000],
            ['clinic_id' => 1, 'doctor_id' => 1, 'patient_name' => 'Дмитрий Морозов', 'patient_phone' => '+7-900-999-9999', 'duration_minutes' => 45, 'status' => 'confirmed', 'price' => 190000],
            ['clinic_id' => 1, 'doctor_id' => 3, 'patient_name' => 'Елена Сорокина', 'patient_phone' => '+7-901-000-0000', 'duration_minutes' => 60, 'status' => 'pending', 'price' => 280000],
        ];

        foreach ($items as $item) {
            $apptDate = now()->addDays(random_int(1, 30));
            MedicalAppointment::updateOrCreate(
                ['patient_phone' => $item['patient_phone'], 'tenant_id' => 1],
                array_merge($item, [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => 1,
                    'appointment_date' => $apptDate,
                ])
            );
        }
    }
}
