<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure\Listeners;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\Services\LockedBonusAwardService;
use App\Domains\Bonuses\ValueObjects\BonusSource;
use App\Domains\Bonuses\ValueObjects\VestingCurve;
use App\Domain\Events\OrderCompleted;
use Illuminate\Support\Facades\Log;

/**
 * AwardBonusOnOrderCompleted - Listener for awarding bonuses on order completion
 * 
 * Automatically awards locked bonuses when an order is completed.
 * Applies cross-vertical multiplier if applicable.
 */
final readonly class AwardBonusOnOrderCompleted
{
    public function __construct(
        private readonly LockedBonusAwardService $awardService,
    ) {}

    public function handle(OrderCompleted $event): void
    {
        $baseRate = config('bonuses.order_rate', 0.03); // 3% default
        $multiplier = $this->getCrossVerticalMultiplier($event);
        $bonusAmount = $event->orderTotal * $baseRate * $multiplier;

        if ($bonusAmount <= 0) {
            return;
        }

        try {
            $dto = new AwardLockedBonusDto(
                userId: $event->userId,
                tenantId: $event->tenantId,
                amount: $bonusAmount,
                source: BonusSource::purchase(),
                vestingCurve: VestingCurve::linear15Days(),
                correlationId: $event->correlationId,
                sourceType: 'order',
                sourceId: $event->orderId,
                metadata: [
                    'order_id' => $event->orderId,
                    'order_total' => $event->orderTotal,
                    'base_rate' => $baseRate,
                    'multiplier' => $multiplier,
                    'vertical' => $event->vertical,
                ],
            );

            $this->awardService->execute($dto);

            Log::info('Bonus awarded on order completed', [
                'order_id' => $event->orderId,
                'user_id' => $event->userId,
                'tenant_id' => $event->tenantId,
                'bonus_amount' => $bonusAmount,
                'multiplier' => $multiplier,
                'vertical' => $event->vertical,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to award bonus on order completed', [
                'order_id' => $event->orderId,
                'user_id' => $event->userId,
                'tenant_id' => $event->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }

    private function getCrossVerticalMultiplier(OrderCompleted $event): float
    {
        // Check if this is a cross-vertical purchase
        $previousOrders = $this->getUserRecentVerticals($event->userId, $event->tenantId);
        
        if (count($previousOrders) === 0) {
            return 1.0;
        }

        $uniqueVerticals = array_unique($previousOrders);
        
        // If user has purchased from different verticals recently, apply multiplier
        if (!in_array($event->vertical, $uniqueVerticals, true)) {
            return 1.25; // 25% bonus for cross-vertical
        }

        return 1.0;
    }

    private function getUserRecentVerticals(int $userId, int $tenantId): array
    {
        // Get recent orders to determine if this is cross-vertical
        // This is a simplified implementation - in production, use proper query
        return [];
    }
}
