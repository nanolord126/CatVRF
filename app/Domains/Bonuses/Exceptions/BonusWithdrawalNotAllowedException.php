<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Exceptions;

/**
 * Исключение: вывод бонусов не разрешён.
 *
 * Выбрасывается при попытке вывести бонусы пользователю,
 * который не имеет права на вывод (не B2B Gold/Platinum).
 */
final class BonusWithdrawalNotAllowedException extends BonusException
{
    public function __construct(
        string $userTier,
        ?string $correlationId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Bonus withdrawal not allowed for user tier: %s',
                $userTier,
            ),
            code: 4004,
            previous: $previous,
            correlationId: $correlationId,
            context: [
                'user_tier' => $userTier,
                'required_tiers' => ['gold', 'platinum'],
            ],
        );
    }

    public function getErrorType(): string
    {
        return 'withdrawal_not_allowed';
    }
}
