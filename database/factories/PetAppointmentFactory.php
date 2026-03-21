<?php declare(strict_types=1);

namespace Database\Factories;

use App\Domains\Pet\Models\PetAppointment;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PetAppointmentFactory extends Factory
{
    protected $model = PetAppointment::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'correlation_id' => $this->faker->uuid(),
            'pet_clinic_id' => 1,
            'vet_id' => 1,
            'pet_name' => $this->faker->firstName(),
            'pet_type' => $this->faker->randomElement(['dog', 'cat', 'bird', 'rabbit', 'hamster']),
            'owner_phone' => $this->faker->phoneNumber(),
            'appointment_date' => $this->faker->dateTimeBetween('+1 days', '+30 days'),
            'service_type' => $this->faker->randomElement(['grooming', 'vaccination', 'checkup', 'treatment']),
            'status' => 'pending',
            'price' => $this->faker->numberBetween(80000, 200000),
        ];
    }

    public function grooming(): self
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'grooming',
            'price' => $this->faker->numberBetween(120000, 200000),
        ]);
    }

    public function vaccination(): self
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'vaccination',
            'price' => $this->faker->numberBetween(80000, 120000),
        ]);
    }
}
