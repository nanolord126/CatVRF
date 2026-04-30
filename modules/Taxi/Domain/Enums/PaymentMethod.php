<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case CARD = 'card';
    case WALLET = 'wallet';
    case CORPORATE = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::CARD => 'Card',
            self::WALLET => 'Wallet',
            self::CORPORATE => 'Corporate',
        };
    }

    public function requiresPreprocessing(): bool
    {
        return in_array($this, [self::WALLET, self::CORPORATE], true);
    }
}
