<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * LogManager — обёртка над Illuminate Log для внедрения зависимостей и моков.
 * CANON 2026 — Production Ready
 */
class LogManager
{
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
