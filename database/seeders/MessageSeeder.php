<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Domains\Communication\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Тестовые сообщения (НЕ ЗАПУСКАТЬ В PRODUCTION).
 */
final class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            ['content' => 'Hello, how are you?', 'status' => 'read'],
            ['content' => 'Great to hear from you!', 'status' => 'read'],
            ['content' => 'Lets catch up soon', 'status' => 'sent'],
        ];

        foreach ($messages as $message) {
            Message::factory()->create(array_merge($message, ['correlation_id' => (string) Str::uuid(), 'tags' => ['source:seeder']]));
        }
    }
}
