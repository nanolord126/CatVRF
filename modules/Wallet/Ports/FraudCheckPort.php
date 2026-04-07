<?php

declare(strict_types=1);

namespace Modules\Wallet\Ports;

/**
 * Исходящий порт: проверка фрода перед операцией с кошельком.
 */
interface FraudCheckPort
{
    /**
     * @param  array<string, mixed>  $context  произвольные дополнительные данные
     * @throws \App\Exceptions\FraudDetectedException
     */
    public function check(
        int    $userId,
        string $operationType,
        int    $amount,
        array  $context = [],
    ): void;
}
