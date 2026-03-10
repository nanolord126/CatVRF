<?php

namespace Database\Factories;

use App\Models\Domains\Communication\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'sender_id' => User::factory(),
            'receiver_id' => User::factory(),
            'content' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['sent', 'read', 'archived']),
            'read_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
