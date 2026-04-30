<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

/**
 * CashbackService — Сервис расчёта и начисления кэшбэка
 * 
 * Управляет процентами кэшбэка, промо-акциями, лимитами
 */
final class CashbackService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    // Базовые проценты кэшбэка по tier
    private const CASHBACK_RATES = [
        'bronze' => 0.01,   // 1%
        'silver' => 0.02,   // 2%
        'gold' => 0.03,     // 3%
        'platinum' => 0.05, // 5%
    ];

    // Лимиты кэшбэка
    private const MAX_CASHBACK_PERCENT = 0.10; // Максимум 10%
    private const MIN_ORDER_AMOUNT = 100; // Минимальная сумма для кэшбэка

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    /**
     * Рассчитать кэшбэк для заказа
     */
    public function calculateCashback(
        int $customerId,
        int $orderAmount,
        string $loyaltyTier = 'bronze',
        ?string $promoCode = null,
        array $categoryMultipliers = []
    ): array {
        return $this->withSpan(
            'supermarket_cashback.calculate',
            function () use ($customerId, $orderAmount, $loyaltyTier, $promoCode, $categoryMultipliers) {
                // Fraud check
                $this->fraudControl->checkCashback($customerId, $orderAmount);

                if ($orderAmount < self::MIN_ORDER_AMOUNT) {
                    return [
                        'eligible' => false,
                        'reason' => 'order_below_minimum',
                        'cashback_amount' => 0,
                        'cashback_percentage' => 0,
                    ];
                }

                $baseRate = self::CASHBACK_RATES[$loyaltyTier] ?? 0.01;
                $finalRate = $baseRate;
                $adjustments = [];

                // Категорийные множители
                if (!empty($categoryMultipliers)) {
                    foreach ($categoryMultipliers as $category => $multiplier) {
                        $finalRate *= $multiplier;
                        $adjustments[] = [
                            'type' => 'category_multiplier',
                            'category' => $category,
                            'multiplier' => $multiplier,
                        ];
                    }
                }

                // Промо-код
                if ($promoCode) {
                    $promoRate = $this->getPromoCashbackRate($promoCode);
                    if ($promoRate > 0) {
                        $finalRate += $promoRate;
                        $adjustments[] = [
                            'type' => 'promo_code',
                            'code' => $promoCode,
                            'rate' => $promoRate,
                        ];
                    }
                }

                // Ограничение максимального процента
                $finalRate = min($finalRate, self::MAX_CASHBACK_PERCENT);

                $cashbackAmount = round($orderAmount * $finalRate);

                // Проверка месячного лимита
                $monthlySpent = $this->getMonthlyCashback($customerId);
                $monthlyLimit = $this->getMonthlyLimit($loyaltyTier);
                
                if ($monthlySpent + $cashbackAmount > $monthlyLimit) {
                    $cashbackAmount = max(0, $monthlyLimit - $monthlySpent);
                    $adjustments[] = [
                        'type' => 'monthly_limit',
                        'limit' => $monthlyLimit,
                        'already_spent' => $monthlySpent,
                    ];
                }

                return [
                    'eligible' => true,
                    'order_amount' => $orderAmount,
                    'base_rate' => $baseRate,
                    'final_rate' => $finalRate,
                    'cashback_amount' => $cashbackAmount,
                    'cashback_percentage' => round($finalRate * 100, 2),
                    'adjustments' => $adjustments,
                    'loyalty_tier' => $loyaltyTier,
                ];
            },
            $this->getStandardAttributes('supermarket', 'cashback_calculate')
        );
    }

    /**
     * Начислить кэшбэк
     */
    public function creditCashback(
        int $customerId,
        int $orderId,
        int $amount,
        string $reason = 'order'
    ): array {
        return $this->withSpan(
            'supermarket_cashback.credit',
            function () use ($customerId, $orderId, $amount, $reason) {
                // TODO: Запись в БД
                Cache::forget("supermarket:cashback:monthly:{$customerId}:" . now()->month);
                
                $this->logAction('cashback_credited', null, [
                    'customer_id' => $customerId,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'reason' => $reason,
                ], null, $customerId);

                return [
                    'customer_id' => $customerId,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'reason' => $reason,
                    'credited_at' => now()->toIso8601String(),
                ];
            },
            $this->getStandardAttributes('supermarket', 'cashback_credit')
        );
    }

    /**
     * Получить ставку кэшбэка по промо-коду
     */
    private function getPromoCashbackRate(string $promoCode): float
    {
        $cacheKey = "supermarket:promo:cashback:{$promoCode}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($promoCode) {
            // TODO: Загрузить из БД
            $promos = [
                'CASHBACK10' => 0.10,
                'WELCOME5' => 0.05,
                'VIP15' => 0.15,
            ];
            
            return $promos[$promoCode] ?? 0;
        });
    }

    /**
     * Получить месячный кэшбэк клиента
     */
    private function getMonthlyCashback(int $customerId): int
    {
        $cacheKey = "supermarket:cashback:monthly:{$customerId}:" . now()->month;
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($customerId) {
            // TODO: Запрос к БД
            return 2500; // mock
        });
    }

    /**
     * Получить месячный лимит
     */
    private function getMonthlyLimit(string $loyaltyTier): int
    {
        return match($loyaltyTier) {
            'platinum' => 10000,
            'gold' => 5000,
            'silver' => 3000,
            default => 1000,
        };
    }

    /**
     * Получить баланс кэшбэка
     */
    public function getCashbackBalance(int $customerId): int
    {
        $cacheKey = "supermarket:cashback:balance:{$customerId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($customerId) {
            // TODO: Запрос к БД
            return 1250; // mock
        });
    }

    /**
     * Использовать кэшбэк как скидку
     */
    public function useCashbackAsDiscount(int $customerId, int $orderAmount, int $requestedAmount): array
    {
        $balance = $this->getCashbackBalance($customerId);
        $maxDiscount = round($orderAmount * 0.5); // Максимум 50% от заказа
        
        $available = min($balance, $maxDiscount, $requestedAmount);
        
        if ($available <= 0) {
            return [
                'success' => false,
                'reason' => 'no_available_cashback',
                'amount' => 0,
            ];
        }

        // TODO: Резервирование кэшбэка
        
        return [
            'success' => true,
            'amount' => $available,
            'remaining_balance' => $balance - $available,
        ];
    }
}
