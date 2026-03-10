<?php

namespace Database\Seeders;

use App\Models\Domains\Clinic\MedicalCard;
use Illuminate\Database\Seeder;

class MedicalCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            ['blood_type' => 'O+', 'allergies' => json_encode(['peanuts', 'penicillin'])],
            ['blood_type' => 'A-', 'allergies' => json_encode([])],
            ['blood_type' => 'B+', 'allergies' => json_encode(['shellfish'])],
        ];

        foreach ($cards as $card) {
            MedicalCard::factory()->create($card);
        }
    }
}
