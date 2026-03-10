<?php

namespace App\Domains\Advertising\Enums;

enum PerformanceMetric: string
{
    case IMPRESSIONS = 'impressions';
    case CLICKS = 'clicks';
    case CTR = 'ctr';                           // Click Through Rate
    case CONVERSIONS = 'conversions';
    case CONVERSION_RATE = 'conversion_rate';
    case CPC = 'cpc';                          // Cost Per Click
    case CPA = 'cpa';                          // Cost Per Action
    case ROI = 'roi';                          // Return on Investment
    case ROAS = 'roas';                        // Return on Ad Spend
    case CPM = 'cpm';                          // Cost Per Thousand Impressions

    public function label(): string
    {
        return match($this) {
            self::IMPRESSIONS => 'Показы',
            self::CLICKS => 'Клики',
            self::CTR => 'CTR (%)',
            self::CONVERSIONS => 'Конверсии',
            self::CONVERSION_RATE => 'Коэффициент конверсии',
            self::CPC => 'Стоимость за клик',
            self::CPA => 'Стоимость за действие',
            self::ROI => 'ROI (%)',
            self::ROAS => 'ROAS',
            self::CPM => 'Стоимость за 1000 показов',
        };
    }

    public function format(): string
    {
        return match($this) {
            self::IMPRESSIONS, self::CLICKS, self::CONVERSIONS => 'number',
            self::CTR, self::CONVERSION_RATE, self::ROI => 'percentage',
            self::CPC, self::CPA, self::CPM => 'currency',
            self::ROAS => 'decimal',
        };
    }
}
