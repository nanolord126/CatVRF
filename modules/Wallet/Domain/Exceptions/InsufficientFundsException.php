<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Exceptions;

final class InsufficientFundsException extends \DomainException
{
    public static function forWithdraw(int $requested, int $available): self
    {
        return new self(
            "Insufficient funds for withdrawal. " .
            "Requested: {$requested} kopeks, Available: {$available} kopeks."
        );
    }

    public static function forHold(int $requested, int $available): self
    {
        return new self(
            "Insufficient funds for hold. " .
            "Requested: {$requested} kopeks, Available: {$available} kopeks."
        );
    }
}
