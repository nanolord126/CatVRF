<?php

declare(strict_types=1);

namespace Database\Factories\Tickets;

use App\Domains\Tickets\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-' . strtoupper(\Illuminate\Support\Str::random(12)),
            'qr_code_data' => 'ticket-' . \Illuminate\Support\Str::uuid()->toString(),
            'ticket_type' => $this->faker->randomElement(['general', 'vip', 'student', 'senior']),
            'price' => $this->faker->numberBetween(50000, 500000),
            'status' => 'active',
            'payment_status' => 'paid',
            'purchased_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'checked_in_at' => null,
            'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            'tags' => ['ticket'],
            'meta' => [],
        ];
    }

    public function pending(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'payment_status' => 'pending',
            ];
        });
    }

    public function checkedIn(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'checked_in',
                'checked_in_at' => now(),
            ];
        });
    }

    public function cancelled(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ];
        });
    }
}
