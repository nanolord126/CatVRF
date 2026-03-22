<?php

declare(strict_types=1);

namespace Database\Factories\Beauty;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Appointment>
 */
final class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+30 days');
        $durationMinutes = $this->faker->randomElement([30, 60, 90, 120]);
        $endTime = (clone $startTime)->modify("+{$durationMinutes} minutes");

        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'salon_id' => 1,
            'master_id' => 1,
            'service_id' => 1,
            'client_id' => 1,
            'uuid' => Str::uuid()->toString(),
            'correlation_id' => Str::uuid()->toString(),
            'datetime_start' => $startTime,
            'datetime_end' => $endTime,
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'price' => $this->faker->numberBetween(150000, 500000), // 1500-5000 руб
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'refunded']),
            'notes' => $this->faker->optional()->sentence(),
            'tags' => [],
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }
}
