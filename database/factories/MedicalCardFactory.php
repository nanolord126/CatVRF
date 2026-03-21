<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domains\Clinic\MedicalCard;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

final class MedicalCardFactory extends Factory
{
    protected $model = MedicalCard::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => User::factory(),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'allergies' => [],
            'medical_history' => [],
            'notes' => fake()->optional(0.5)->sentence(),
            'last_check_up' => fake()->dateTimeBetween('-6 months'),
        ];
    }
}
