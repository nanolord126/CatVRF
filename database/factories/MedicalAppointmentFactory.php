<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Medical\MedicalHealthcare\Models\MedicalAppointment;
use Illuminate\Database\Eloquent\Factories\Factory;

final class MedicalAppointmentFactory extends Factory
{
    protected $model = MedicalAppointment::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'clinic_id' => 1,
            'doctor_id' => 1,
            'patient_name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'patient_phone' => $this->faker->phoneNumber(),
            'appointment_date' => $this->faker->dateTimeBetween('+1 days', '+30 days'),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60]),
            'status' => 'pending',
            'price' => $this->faker->numberBetween(100000, 300000),
            'notes' => $this->faker->optional()->text(100),
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
