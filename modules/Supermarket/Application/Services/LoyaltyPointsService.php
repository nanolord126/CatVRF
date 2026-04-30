<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

/**
 * LoyaltyPointsService — Сервис расчёта и управления лояльными баллами
 * 
 * Начисляет баллы за покупки, действия, реферальные программы
 */
final class LoyaltyPointsService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    // Коэффициенты начисления баллов
    private const POINTS_PER_RUBLE = 1; // 1 балл за 1 рубль
    private const POINTS_MULTIPLIER_TIER = [
        'bronze' => 1.0,
        'silver' => 1.5,
        'gold' => 2.0,
        'platinum' => 3.0,
    ];

    // Бонусы за действия
    private const POINTS_FIRST_ORDER = 500;
    private const POINTS_REVIEW = 50;
    private const POINTS_REFERRAL = 1000;
    private const POINTS_BIRTHDAY = 2000;
    private const POINTS_WEEKLY_ORDER = 100;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    /**
     * Начислить баллы за заказ
     */
    public function earnPointsForOrder(
        int $customerId,
        int $orderId,
        int $orderAmount,
        string $loyaltyTier = 'bronze',
        array $bonuses = []
    ): array {
        return $this->withSpan(
            'supermarket_loyalty.earn_points_order',
            function () use ($customerId, $orderId, $orderAmount, $loyaltyTier, $bonuses) {
                // Fraud check
                $this->fraudControl->checkLoyaltyPoints($customerId, $orderAmount);

                $basePoints = $orderAmount * self::POINTS_PER_RUBLE;
                $tierMultiplier = self::POINTS_MULTIPLIER_TIER[$loyaltyTier] ?? 1.0;
                
                $points = round($basePoints * $tierMultiplier);
                
                // Дополнительные бонусы
                foreach ($bonuses as $bonus) {
                    $points += $this->calculateBonusPoints($bonus);
                }

                // Проверка на первый заказ
                if ($this->isFirstOrder($customerId)) {
                    $points += self::POINTS_FIRST_ORDER;
                }

                // Проверка на еженедельный бонус
                if ($this->isWeeklyOrder($customerId)) {
                    $points += self::POINTS_WEEKLY_ORDER;
                }

                // Начисление баллов
                $result = $this->creditPoints($customerId, $points, 'order', $orderId);

                $this->logAction('loyalty_points_earned', null, [
                    'customer_id' => $customerId,
                    'order_id' => $orderId,
                    'points' => $points,
                    'order_amount' => $orderAmount,
                    'loyalty_tier' => $loyaltyTier,
                ], null, $customerId);

                return $result;
            },
            $this->getStandardAttributes('supermarket', 'loyalty_earn_points')
        );
    }

    /**
     * Списать баллы
     */
    public function redeemPoints(
        int $customerId,
        int $points,
        string $reason,
        ?int $orderId = null
    ): array {
        return $this->withSpan(
            'supermarket_loyalty.redeem_points',
            function () use ($customerId, $points, $reason, $orderId) {
                $currentBalance = $this->getCustomerBalance($customerId);
                
                if ($currentBalance < $points) {
                    throw new \Exception("Insufficient points balance");
                }

                $result = $this->debitPoints($customerId, $points, $reason, $orderId);

                $this->logAction('loyalty_points_redeemed', null, [
                    'customer_id' => $customerId,
                    'points' => $points,
                    'reason' => $reason,
                    'order_id' => $orderId,
                ], null, $customerId);

                return $result;
            },
            $this->getStandardAttributes('supermarket', 'loyalty_redeem_points')
        );
    }

    /**
     * Получить баланс клиента
     */
    public function getCustomerBalance(int $customerId): int
    {
        $cacheKey = "supermarket:loyalty:balance:{$customerId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($customerId) {
            // TODO: Запрос к БД
            return 1250; // mock
        });
    }

    /**
     * Начислить баллы
     */
    private function creditPoints(int $customerId, int $points, string $source, ?int $referenceId = null): array
    {
        // TODO: Запись в БД
        Cache::forget("supermarket:loyalty:balance:{$customerId}");
        
        return [
            'customer_id' => $customerId,
            'points' => $points,
            'type' => 'credit',
            'source' => $source,
            'reference_id' => $referenceId,
            'new_balance' => $this->getCustomerBalance($customerId) + $points,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Списать баллы
     */
    private function debitPoints(int $customerId, int $points, string $reason, ?int $referenceId = null): array
    {
        // TODO: Запись в БД
        Cache::forget("supermarket:loyalty:balance:{$customerId}");
        
        return [
            'customer_id' => $customerId,
            'points' => $points,
            'type' => 'debit',
            'reason' => $reason,
            'reference_id' => $referenceId,
            'new_balance' => $this->getCustomerBalance($customerId) - $points,
            'created_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Рассчитать бонусные баллы
     */
    private function calculateBonusPoints(string $bonusType): int
    {
        return match($bonusType) {
            'review' => self::POINTS_REVIEW,
            'referral' => self::POINTS_REFERRAL,
            'birthday' => self::POINTS_BIRTHDAY,
            default => 0,
        };
    }

    /**
     * Проверка на первый заказ
     */
    private function isFirstOrder(int $customerId): bool
    {
        $cacheKey = "supermarket:loyalty:first_order:{$customerId}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($customerId) {
            // TODO: Проверка в БД
            return false; // mock
        });
    }

    /**
     * Проверка на еженедельный заказ
     */
    private function isWeeklyOrder(int $customerId): bool
    {
        $cacheKey = "supermarket:loyalty:weekly:{$customerId}:" . now()->weekOfYear;
        
        return !Cache::has($cacheKey);
    }

    /**
     * Обновить tier лояльности
     */
    public function updateLoyaltyTier(int $customerId, int $totalSpent): string
    {
        $tier = match(true) {
            $totalSpent >= 100000 => 'platinum',
            $totalSpent >= 50000 => 'gold',
            $totalSpent >= 20000 => 'silver',
            default => 'bronze',
        };

        // TODO: Обновить в БД
        
        return $tier;
    }

    /**
     * Конвертация баллов в рубли
     */
    public function convertPointsToRubles(int $points): int
    {
        const POINT_TO_RUBLE_RATE = 0.5; // 1 балл = 0.5 рубля
        return round($points * POINT_TO_RUBLE_RATE);
    }
}
