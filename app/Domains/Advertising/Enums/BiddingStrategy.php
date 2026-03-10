<?php

namespace App\Domains\Advertising\Enums;

enum BiddingStrategy: string
{
    case CPC = 'cpc';           // Cost Per Click
    case CPM = 'cpm';           // Cost Per Thousand Impressions
    case CPA = 'cpa';           // Cost Per Action
    case ROAS = 'roas';         // Return On Ad Spend
    case MANUAL = 'manual';

    public function label(): string
    {
        return match($this) {
            self::CPC => 'Стоимость за клик',
            self::CPM => 'Стоимость за 1000 показов',
            self::CPA => 'Стоимость за действие',
            self::ROAS => 'Коэффициент возврата',
            self::MANUAL => 'Ручное управление',
        };
    }

    public function abbreviation(): string
    {
        return $this->value;
    }

    public function isAutomatic(): bool
    {
        return $this !== self::MANUAL;
    }
}
