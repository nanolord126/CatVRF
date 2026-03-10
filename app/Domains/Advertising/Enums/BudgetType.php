<?php

namespace App\Domains\Advertising\Enums;

enum BudgetType: string
{
    case DAILY = 'daily';
    case LIFETIME = 'lifetime';
    case IMPRESSION_BASED = 'impression_based';

    public function label(): string
    {
        return match($this) {
            self::DAILY => 'Дневной бюджет',
            self::LIFETIME => 'Общий бюджет',
            self::IMPRESSION_BASED => 'По показам',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::DAILY => 'Ограничение расходов на день',
            self::LIFETIME => 'Ограничение общего расходов за весь период',
            self::IMPRESSION_BASED => 'Платежи за количество показов',
        };
    }
}
