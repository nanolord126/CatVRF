<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Exceptions;

/**
 * Исключение: бонус истёк.
 *
 * Выбрасывается при попытке использовать бонус с истёкшим сроком действия.
 */
final class BonusExpiredException extends BonusException
{
    public function __construct(
        string $bonusUuid,
        string $expiryDate,
        ?string $correlationId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: sprintf(
                'Bonus %s expired on %s',
                $bonusUuid,
                $expiryDate,
            ),
            code: 4005,
            previous: $previous,
            correlationId: $correlationId,
            context: [
                'bonus_uuid' => $bonusUuid,
                'expiry_date' => $expiryDate,
            ],
        );
    }

    public function getErrorType(): string
    {
        return 'bonus_expired';
    }
}
