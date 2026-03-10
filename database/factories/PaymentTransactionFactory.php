<?php

namespace Database\Factories;

use App\Domains\Finances\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Finances\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => 'pay_' . Str::random(20),
            'user_id' => User::factory(),
            'tenant_id' => null,
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'status' => $this->faker->randomElement([
                PaymentTransaction::STATUS_PENDING,
                PaymentTransaction::STATUS_AUTHORIZED,
                PaymentTransaction::STATUS_SETTLED,
            ]),
            'splits' => null,
            'metadata' => [
                'order_id' => 'ORD-' . Str::random(10),
                'order_type' => 'course_enrollment',
                'user_email' => $this->faker->email(),
            ],
            'correlation_id' => Str::uuid(),
            'captured_at' => $this->faker->optional()->dateTime(),
        ];
    }

    /**
     * Состояние для успешного платежа.
     */
    public function settled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_SETTLED,
            'captured_at' => now(),
        ]);
    }

    /**
     * Состояние для ошибочного платежа.
     */
    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_FAILED,
        ]);
    }

    /**
     * Состояние для возвращённого платежа.
     */
    public function refunded(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_REFUNDED,
            'captured_at' => now(),
        ]);
    }
}
