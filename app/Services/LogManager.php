<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LogManager extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function info(string $message, array $context = []): void
        {
            Log::info($message, $context);
        }

        public function warn(string $message, array $context = []): void
        {
            Log::warning($message, $context);
        }

        public function warning(string $message, array $context = []): void
        {
            Log::warning($message, $context);
        }

        public function error(string $message, array $context = []): void
        {
            Log::error($message, $context);
        }

        public function debug(string $message, array $context = []): void
        {
            Log::debug($message, $context);
        }

        public function channel(string $channel): static
        {
            return $this;
        }
}
