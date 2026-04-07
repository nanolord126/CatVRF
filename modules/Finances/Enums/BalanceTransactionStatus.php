<?php

declare(strict_types=1);

namespace Modules\Finances\Enums;

enum BalanceTransactionStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
