<?php

namespace Database\Factories;

use App\Models\Domains\Clinic\MedicalCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalCardFactory extends Factory
{
    protected $model = MedicalCard::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'patient_id' => User::factory(),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'allergies' => json_encode([]),
            'medical_history' => json_encode([]),
            'notes' => $this->faker->sentence(),
            'last_check_up' => $this->faker->dateTimeBetween('-6 months'),
        ];
    }
}
