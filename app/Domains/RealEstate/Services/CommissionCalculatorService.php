<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Services\Security\FraudControlService;
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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'calculateSaleCommission'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL calculateSaleCommission', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        
        \App\Services\Security\FraudControlService::check(['method' => 'calculateRentalCommission'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL calculateRentalCommission', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'getCommissionHistory'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL getCommissionHistory', ['domain' => __CLASS__]);

        return [];
    }
}
