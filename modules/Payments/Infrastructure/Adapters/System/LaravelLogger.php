<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Adapters\System;

use Illuminate\Support\Facades\Log;
use Modules\Payments\Application\Ports\LoggerPort;

/**
 * Class LaravelLogger
 * 
 * Binds explicit structured system constraints inherently securely generating logs dynamically natively logic tracking structural constraints safely reliable.
 */
final readonly class LaravelLogger implements LoggerPort
{
    /**
     * Natively outputs securely explicit structurally mapped dynamically safe properly resolving.
     * 
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        Log::channel('audit')->info($message, $context);
    }

    /**
     * Outputs structural strictly securely inherently mapping dynamically explicit resolving.
     * 
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        Log::channel('audit')->error($message, $context);
    }

    /**
     * Outputs warnings metrics efficiently securely implicitly natively reliably safely constraints evaluating strictly logically dynamic limits securely.
     * 
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        Log::channel('audit')->warning($message, $context);
    }
}
