<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

enum GatewayProvider: string
{
    case YOOKASSA = 'yookassa';
    case TINKOFF = 'tinkoff';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::YOOKASSA => 'YooKassa',
            self::TINKOFF => 'Tinkoff',
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
        };
    }

    public function supportsRecurring(): bool
    {
        return match ($this) {
            self::YOOKASSA, self::STRIPE => true,
            default => false,
        };
    }
}
