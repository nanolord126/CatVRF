<?php

namespace Database\Seeders;

use App\Models\Domains\Communication\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            ['content' => 'Hello, how are you?', 'status' => 'read'],
            ['content' => 'Great to hear from you!', 'status' => 'read'],
            ['content' => 'Lets catch up soon', 'status' => 'sent'],
        ];

        foreach ($messages as $message) {
            Message::factory()->create($message);
        }
    }
}
