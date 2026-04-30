<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\CatCRM\Domain\Verticals\Fitness\FitnessBooking;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\CatCRM\Domain\Verticals\Fitness\FitnessBooking>
 */
class FitnessBookingFactory extends Factory
{
    protected $model = FitnessBooking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bookingType = fake()->randomElement(['personal_training', 'group_workout', 'class', 'open_gym', 'trial']);
        $isGroup = $bookingType === 'group_workout' || $bookingType === 'class';
        
        return [
            'tenant_id' => 1,
            'business_group_id' => null,
            'deal_id' => null,
            'customer_id' => null,
            'membership_id' => fake()->numberBetween(1, 50),
            'venue_id' => fake()->numberBetween(1, 5),
            'trainer_id' => $bookingType === 'personal_training' ? fake()->numberBetween(1, 20) : fake()->optional(0.7)->numberBetween(1, 20),
            'workout_type_id' => fake()->numberBetween(1, 15),
            'schedule_slot_id' => fake()->numberBetween(1, 100),
            'booking_type' => $bookingType,
            'workout_name' => fake()->randomElement(['Силовая тренировка', 'Йога', 'Пилатес', 'Кроссфит', 'Кардио', 'Стретчинг', 'Бокс', 'Плавание']),
            'workout_intensity' => fake()->randomElement(['very_low', 'low', 'moderate', 'high', 'very_high']),
            'is_group_workout' => $isGroup,
            'booking_date' => fake()->dateTimeBetween('now', '+30 days'),
            'start_time' => fake()->time(),
            'end_time' => fake()->time(),
            'duration_minutes' => fake()->randomElement([45, 60, 90, 120]),
            'status' => fake()->randomElement(['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show']),
            'check_in_time' => fake()->optional(0.6)->dateTime(),
            'check_out_time' => fake()->optional(0.5)->dateTime(),
            'attendance_status' => fake()->optional(0.6)->randomElement(['present', 'absent', 'late', 'excused']),
            'cancellation_reason' => fake()->optional(0.1)->text(),
            'no_show_reason' => fake()->optional(0.05)->text(),
            'price' => $bookingType === 'trial' ? 0 : fake()->randomFloat(2, 500, 5000),
            'discount_percent' => fake()->randomFloat(2, 0, 30),
            'discount_amount' => fake()->randomFloat(2, 0, 500),
            'final_price' => 0, // Автоматически рассчитается в booted
            'payment_status' => fake()->randomElement(['pending', 'paid', 'partial', 'refunded']),
            'payment_method' => fake()->randomElement(['cash', 'card', 'online', 'membership']),
            'is_paid' => fake()->boolean(70),
            'reminder_sent' => fake()->boolean(80),
            'follow_up_sent' => fake()->boolean(40),
            'trainer_notes' => fake()->optional(0.4)->text(),
            'client_notes' => fake()->optional(0.3)->text(),
            'performance_metrics' => fake()->optional(0.3)->randomElement([null, ['reps' => 15, 'sets' => 3], ['distance_km' => 5, 'pace' => '5:30']]),
            'calories_burned' => fake()->optional(0.5)->numberBetween(200, 800),
            'heart_rate_avg' => fake()->optional(0.5)->numberBetween(120, 170),
            'heart_rate_max' => fake()->optional(0.5)->numberBetween(150, 190),
            'session_rating' => fake()->optional(0.6)->numberBetween(1, 5),
            'session_feedback' => fake()->optional(0.4)->text(),
            'is_trial' => $bookingType === 'trial',
            'is_corporate' => fake()->boolean(10),
            'corporate_company_id' => fake()->optional(0.1)->numberBetween(1, 10),
            'special_program_type' => fake()->optional(0.15)->randomElement(['seasonal', 'prenatal', 'senior', 'kids']),
            'special_program_enrollment_id' => fake()->optional(0.15)->numberBetween(1, 20),
            'metadata' => fake()->optional(0.2)->randomElement([null, ['source' => 'mobile_app'], ['source' => 'website', 'device' => 'desktop']]),
            'correlation_id' => fake()->optional(0.5)->uuid(),
        ];
    }

    /**
     * Personal training booking state
     */
    public function personalTraining(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => 'personal_training',
            'is_group_workout' => false,
            'trainer_id' => fake()->numberBetween(1, 20),
            'price' => fake()->randomFloat(2, 1500, 5000),
        ]);
    }

    /**
     * Group workout booking state
     */
    public function groupWorkout(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => 'group_workout',
            'is_group_workout' => true,
            'price' => fake()->randomFloat(2, 300, 1000),
        ]);
    }

    /**
     * Trial booking state
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_type' => 'trial',
            'is_trial' => true,
            'price' => 0,
            'final_price' => 0,
        ]);
    }

    /**
     * Corporate booking state
     */
    public function corporate(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_corporate' => true,
            'corporate_company_id' => fake()->numberBetween(1, 10),
            'price' => fake()->randomFloat(2, 300, 800),
        ]);
    }

    /**
     * Completed booking state
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'check_in_time' => fake()->dateTimeBetween('-2 hours', '-1 hour'),
            'check_out_time' => fake()->dateTimeBetween('-1 hour', 'now'),
            'attendance_status' => fake()->randomElement(['present', 'late']),
            'calories_burned' => fake()->numberBetween(200, 800),
            'heart_rate_avg' => fake()->numberBetween(120, 170),
            'session_rating' => fake()->numberBetween(1, 5),
        ]);
    }

    /**
     * Confirmed booking state
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Checked in booking state
     */
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'checked_in',
            'check_in_time' => fake()->dateTimeBetween('-30 minutes', 'now'),
            'attendance_status' => 'present',
        ]);
    }
}
