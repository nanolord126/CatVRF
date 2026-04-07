<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

/**
 * Port: Логирование (outgoing).
 * Реализуется в Infrastructure через LaravelLoggerAdapter.
 * UseCases никогда не импортируют Log::
 */
interface LoggerPort
{
    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function critical(string $message, array $context = []): void;

    /** Audit channel — все финансовые мутации */
    public function audit(string $message, array $context = []): void;
}
