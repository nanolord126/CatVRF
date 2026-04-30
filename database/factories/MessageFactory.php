<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domains\Communication\Message;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'tenant_id' => DB::table('tenants')->inRandomOrder()->value('id') ?? Tenant::factory(),
            'content' => fake()->sentence(),
            'status' => 'sent',
            'sender_id' => 1,
            'receiver_id' => 2,
        ];
    }
}
