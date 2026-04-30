<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Enums;

enum SegmentType: string
{
    case HIGH_VALUE = 'high_value';
    case LOYAL = 'loyal';
    case CHURN_RISK = 'churn_risk';
    case NEW = 'new';
    case DORMANT = 'dormant';
    case VIP = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::HIGH_VALUE => 'High Value',
            self::LOYAL => 'Loyal',
            self::CHURN_RISK => 'Churn Risk',
            self::NEW => 'New',
            self::DORMANT => 'Dormant',
            self::VIP => 'VIP',
        };
    }
}
