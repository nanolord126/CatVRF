<?php

declare(strict_types=1);

namespace Modules\Finances\Enums;

enum BalanceTransactionType: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAWAL = 'withdrawal';
    case COMMISSION = 'commission';
    case BONUS = 'bonus';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
}
