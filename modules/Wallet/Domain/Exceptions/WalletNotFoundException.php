<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Exceptions;

final class WalletNotFoundException extends \DomainException
{
    public static function forUser(int $userId, int $tenantId): self
    {
        return new self("Wallet not found for user {$userId} in tenant {$tenantId}");
    }
}
