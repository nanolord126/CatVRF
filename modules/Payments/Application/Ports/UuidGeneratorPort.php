<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

/**
 * Port: Генерация UUID (outgoing).
 * Изолирует UseCases от Str::uuid() / ramsey/uuid.
 */
interface UuidGeneratorPort
{
    public function generate(): string;
}
