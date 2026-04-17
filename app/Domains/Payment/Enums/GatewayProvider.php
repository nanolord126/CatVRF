<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

/**
 * Payment Gateway Providers.
 */
enum GatewayProvider: string
{
    case YOOKASSA = 'yookassa';
    case TINKOFF = 'tinkoff';
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
}
