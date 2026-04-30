<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Exceptions;

/**
 * Исключение: превышен лимит бонусов.
 *
 * Выбрасывается при попытке начислить бонусы сверх установленного лимита
 * (max_bonus_balance в конфиге).
 */
final class BonusLimitExceededException extends BonusException
{
    public function __construct(
        int $currentBalance,
        int $requestedAmount,
        int $maxBalance,
        ?string $correlationId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Bonus limit exceeded: current %d cents + requested %d cents exceeds max %d cents',
                $currentBalance,
                $requestedAmount,
                $maxBalance,
            ),
            code: 4003,
            previous: $previous,
            correlationId: $correlationId,
            context: [
                'current_balance' => $currentBalance,
                'requested_amount' => $requestedAmount,
                'max_balance' => $maxBalance,
                'excess' => ($currentBalance + $requestedAmount) - $maxBalance,
            ],
        );
    }

    public function getErrorType(): string
    {
        return 'limit_exceeded';
    }
}
