<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use Illuminate\Support\Facades\Log;

/**
 * Service для расчёта комиссии при продаже/аренде.
 * Production 2026.
 */
final class CommissionCalculatorService
{
    public const COMMISSION_PERCENT = 14.0; // Стандартная комиссия 14%

    public function calculateSaleCommission(int $salePrice, string $correlationId = ''): array
    {
        $commission = (int) ($salePrice * self::COMMISSION_PERCENT / 100);

        Log::channel('audit')->info('Sale commission calculated', [
            'sale_price' => $salePrice,
            'commission_percent' => self::COMMISSION_PERCENT,
            'commission' => $commission,
            'owner_gets' => $salePrice - $commission,
            'correlation_id' => $correlationId,
        ]);

        return [
            'commission' => $commission,
            'owner_gets' => $salePrice - $commission,
        ];
    }

    public function calculateRentalCommission(int $rentPriceMonth, string $correlationId = ''): array
    {
        $commission = (int) ($rentPriceMonth * self::COMMISSION_PERCENT / 100);

        Log::channel('audit')->info('Rental commission calculated', [
            'rent_price_month' => $rentPriceMonth,
            'commission_percent' => self::COMMISSION_PERCENT,
            'commission' => $commission,
            'owner_gets' => $rentPriceMonth - $commission,
            'correlation_id' => $correlationId,
        ]);

        return [
            'commission' => $commission,
            'owner_gets' => $rentPriceMonth - $commission,
        ];
    }

    public function getCommissionHistory(int $propertyId, string $period = 'month'): array
    {
        return [];
    }
}
