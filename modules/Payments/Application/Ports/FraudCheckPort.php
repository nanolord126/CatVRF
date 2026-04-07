<?php

declare(strict_types=1);

namespace Modules\Payments\Application\Ports;

/**
 * Port: Fraud-проверка (outgoing).
 * Реализуется адаптером к FraudMLService.
 * Выбрасывает DomainException при обнаружении фрода.
 */
interface FraudCheckPort
{
    /**
     * @param  array<string, mixed>  $context
     * @throws \DomainException
     */
    public function check(
        int    $userId,
        string $operationType,
        int    $amount,
        array  $context = [],
    ): void;
}
