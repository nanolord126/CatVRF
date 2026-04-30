<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Exceptions;

/**
 * Исключение: недостаточно бонусов на балансе.
 *
 * Выбрасывается при попытке потратить или вывести больше бонусов,
 * чем доступно на балансе пользователя.
 */
final class InsufficientBonusBalanceException extends BonusException
{
    public function __construct(
        int $requestedAmount,
        int $availableAmount,
        ?string $correlationId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Insufficient bonus balance: requested %d cents, available %d cents',
                $requestedAmount,
                $availableAmount,
            ),
            code: 4002,
            previous: $previous,
            correlationId: $correlationId,
            context: [
                'requested_amount' => $requestedAmount,
                'available_amount' => $availableAmount,
                'shortage' => $requestedAmount - $availableAmount,
            ],
        );
    }

    public function getErrorType(): string
    {
        return 'insufficient_balance';
    }
}
