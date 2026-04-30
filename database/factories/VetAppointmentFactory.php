<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CatCRM\Domain\Verticals\VetGrooming\VetAppointment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\CatCRM\Domain\Verticals\VetGrooming\VetAppointment>
 */
class VetAppointmentFactory extends Factory
{
    protected $model = VetAppointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appointmentType = fake()->randomElement(['checkup', 'vaccination', 'grooming', 'surgery', 'emergency', 'dental', 'consultation']);
        $serviceCategory = $appointmentType === 'grooming' ? 'grooming' : 'veterinary';
        
        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'deal_id' => null,
            'customer_id' => null,
            'pet_id' => fake()->numberBetween(1, 100),
            'appointment_type' => $appointmentType,
            'service_category' => $serviceCategory,
            'specific_service' => fake()->randomElement(['Полный осмотр', 'Вакцинация DHP', 'Стрижка', 'Кастрация', 'Чистка зубов', 'Консультация']),
            'veterinarian_id' => $serviceCategory === 'veterinary' ? fake()->numberBetween(1, 10) : null,
            'groomer_id' => $serviceCategory === 'grooming' ? fake()->numberBetween(1, 10) : null,
            'appointment_date' => fake()->dateTimeBetween('now', '+30 days'),
            'appointment_time' => fake()->time(),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'status' => fake()->randomElement(['pending', 'confirmed', 'checked_in', 'in_progress', 'completed', 'cancelled']),
            'check_in_time' => fake()->optional(0.7)->dateTime(),
            'start_time' => fake()->optional(0.5)->dateTime(),
            'end_time' => fake()->optional(0.4)->dateTime(),
            'weight' => fake()->optional(0.8)->randomFloat(2, 0.5, 80),
            'temperature' => fake()->optional(0.8)->randomFloat(1, 38.0, 40.0),
            'symptoms' => fake()->optional(0.6)->randomElement([null, ['Рвота', 'Понос'], ['Кашель'], ['Летаргия'], ['Потеря аппетита']]),
            'diagnosis' => fake()->optional(0.5)->text(),
            'treatment' => fake()->optional(0.5)->text(),
            'medications' => fake()->optional(0.5)->randomElement([null, ['Амоксициллин 500мг'], ['Ивермектин'], ['Витамины']]),
            'follow_up_date' => fake()->optional(0.3)->dateTimeBetween('+7 days', '+30 days'),
            'follow_up_notes' => fake()->optional(0.3)->text(),
            'vaccination_administered' => $appointmentType === 'vaccination' ? true : false,
            'vaccination_type' => $appointmentType === 'vaccination' ? fake()->randomElement(['DHP', 'Бешенство', 'Лептоспироз', 'Боррелиоз']) : null,
            'vaccination_batch' => $appointmentType === 'vaccination' ? fake()->bothify('BATCH-####') : null,
            'grooming_services' => $serviceCategory === 'grooming' ? fake()->randomElement([['Стрижка'], ['Стрижка', 'Купание'], ['Когти', 'Уши'], ['Полный груминг']]) : null,
            'grooming_notes' => $serviceCategory === 'grooming' ? fake()->optional(0.5)->text() : null,
            'behavior_rating' => fake()->optional(0.7)->randomElement(['excellent', 'good', 'fair', 'poor']),
            'behavior_notes' => fake()->optional(0.5)->text(),
            'before_photos' => fake()->optional(0.3)->randomElement([null, ['photo1.jpg'], ['photo1.jpg', 'photo2.jpg']]),
            'after_photos' => fake()->optional(0.2)->randomElement([null, ['after1.jpg'], ['after1.jpg', 'after2.jpg']]),
            'total_price' => fake()->randomFloat(2, 500, 15000),
            'discount_percent' => fake()->randomFloat(2, 0, 20),
            'discount_amount' => fake()->randomFloat(2, 0, 1000),
            'final_price' => 0, // Автоматически рассчитается в booted
            'payment_status' => fake()->randomElement(['pending', 'paid', 'partial', 'refunded']),
            'payment_method' => fake()->randomElement(['cash', 'card', 'online']),
            'reminder_sent' => fake()->boolean(80),
            'no_show' => fake()->boolean(5),
            'cancellation_reason' => fake()->optional(0.1)->text(),
            'notes' => fake()->optional(0.3)->text(),
            'metadata' => fake()->optional(0.2)->randomElement([null, ['source' => 'website'], ['source' => 'call_center', 'priority' => 'high']]),
            'correlation_id' => fake()->optional(0.5)->uuid(),
        ];
    }

    /**
     * Veterinary appointment state
     */
    public function veterinary(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_category' => 'veterinary',
            'appointment_type' => fake()->randomElement(['checkup', 'vaccination', 'surgery', 'emergency', 'dental', 'consultation']),
            'veterinarian_id' => fake()->numberBetween(1, 10),
            'groomer_id' => null,
        ]);
    }

    /**
     * Grooming appointment state
     */
    public function grooming(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_category' => 'grooming',
            'appointment_type' => 'grooming',
            'veterinarian_id' => null,
            'groomer_id' => fake()->numberBetween(1, 10),
        ]);
    }

    /**
     * Vaccination appointment state
     */
    public function vaccination(): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_type' => 'vaccination',
            'service_category' => 'veterinary',
            'vaccination_administered' => true,
            'vaccination_type' => fake()->randomElement(['DHP', 'Бешенство', 'Лептоспироз', 'Боррелиоз']),
            'vaccination_batch' => fake()->bothify('BATCH-####'),
        ]);
    }

    /**
     * Completed appointment state
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'check_in_time' => fake()->dateTimeBetween('-2 hours', '-1 hour'),
            'start_time' => fake()->dateTimeBetween('-2 hours', '-1 hour'),
            'end_time' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Confirmed appointment state
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }
}
