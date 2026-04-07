<?php

declare(strict_types=1);

namespace Modules\Payments\Ports;

/**
 * Outgoing Port: Fraud-проверка.
 */
interface FraudCheckPort
{
    /**
     * Проверить операцию на фрод.
     * Выбрасывает исключение если обнаружен фрод.
     *
     * @param  array<string, mixed>  $context  IP, device fingerprint, correlation_id и т.д.
     * @throws \DomainException
     */
    public function check(
        int    $userId,
        string $operationType,
        int    $amount,
        array  $context = [],
    ): void;
}
