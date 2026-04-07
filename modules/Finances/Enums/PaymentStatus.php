<?php

declare(strict_types=1);

namespace Modules\Finances\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
}
