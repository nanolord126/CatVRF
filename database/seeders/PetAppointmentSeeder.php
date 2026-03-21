<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Pet\Models\PetAppointment;
use Illuminate\Database\Seeder;

final class PetAppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['pet_clinic_id' => 1, 'vet_id' => 1, 'pet_name' => 'Барс', 'pet_type' => 'dog', 'owner_phone' => '+7-910-111-1111', 'service_type' => 'grooming', 'status' => 'completed', 'price' => 150000],
            ['pet_clinic_id' => 1, 'vet_id' => 2, 'pet_name' => 'Мурзик', 'pet_type' => 'cat', 'owner_phone' => '+7-910-222-2222', 'service_type' => 'vaccination', 'status' => 'completed', 'price' => 100000],
            ['pet_clinic_id' => 1, 'vet_id' => 1, 'pet_name' => 'Попугай Кеша', 'pet_type' => 'bird', 'owner_phone' => '+7-910-333-3333', 'service_type' => 'checkup', 'status' => 'pending', 'price' => 120000],
            ['pet_clinic_id' => 1, 'vet_id' => 3, 'pet_name' => 'Кролик Пушок', 'pet_type' => 'rabbit', 'owner_phone' => '+7-910-444-4444', 'service_type' => 'treatment', 'status' => 'confirmed', 'price' => 180000],
            ['pet_clinic_id' => 1, 'vet_id' => 2, 'pet_name' => 'Хомяк Вася', 'pet_type' => 'hamster', 'owner_phone' => '+7-910-555-5555', 'service_type' => 'checkup', 'status' => 'completed', 'price' => 80000],
            ['pet_clinic_id' => 1, 'vet_id' => 1, 'pet_name' => 'Лайка', 'pet_type' => 'dog', 'owner_phone' => '+7-910-666-6666', 'service_type' => 'grooming', 'status' => 'pending', 'price' => 160000],
            ['pet_clinic_id' => 1, 'vet_id' => 3, 'pet_name' => 'Пёс Рекс', 'pet_type' => 'dog', 'owner_phone' => '+7-910-777-7777', 'service_type' => 'vaccination', 'status' => 'cancelled', 'price' => 110000],
            ['pet_clinic_id' => 1, 'vet_id' => 2, 'pet_name' => 'Ясь', 'pet_type' => 'cat', 'owner_phone' => '+7-910-888-8888', 'service_type' => 'grooming', 'status' => 'completed', 'price' => 130000],
            ['pet_clinic_id' => 1, 'vet_id' => 1, 'pet_name' => 'Петя', 'pet_type' => 'bird', 'owner_phone' => '+7-910-999-9999', 'service_type' => 'checkup', 'status' => 'confirmed', 'price' => 95000],
            ['pet_clinic_id' => 1, 'vet_id' => 3, 'pet_name' => 'Батон', 'pet_type' => 'rabbit', 'owner_phone' => '+7-911-000-0000', 'service_type' => 'treatment', 'status' => 'pending', 'price' => 200000],
        ];

        foreach ($items as $item) {
            $apptDate = now()->addDays(random_int(1, 30));
            PetAppointment::updateOrCreate(
                ['owner_phone' => $item['owner_phone'], 'tenant_id' => 1],
                array_merge($item, [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'tenant_id' => 1,
                    'appointment_date' => $apptDate,
                ])
            );
        }
    }
}
