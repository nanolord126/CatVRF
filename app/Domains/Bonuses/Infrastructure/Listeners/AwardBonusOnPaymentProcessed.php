<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Infrastructure\Listeners;

use App\Domains\Bonuses\DTOs\AwardLockedBonusDto;
use App\Domains\Bonuses\Services\LockedBonusAwardService;
use App\Domains\Bonuses\ValueObjects\BonusSource;
use App\Domains\Bonuses\ValueObjects\VestingCurve;
use App\Domains\Payments\Events\PaymentProcessed;
use Illuminate\Support\Facades\Log;

/**
 * AwardBonusOnPaymentProcessed - Listener for awarding bonuses on payment
 * 
 * Automatically awards locked bonuses when a payment is successfully processed.
 * Uses purchase rate from config.
 */
final readonly class AwardBonusOnPaymentProcessed
{
    public function __construct(
        private readonly LockedBonusAwardService $awardService,
    ) {}

    public function handle(PaymentProcessed $event): void
    {
        $purchaseRate = config('bonuses.purchase_rate', 0.05); // 5% default
        $bonusAmount = $event->amount * $purchaseRate;

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
                sourceType: 'payment',
                sourceId: $event->paymentId,
                metadata: [
                    'payment_id' => $event->paymentId,
                    'order_amount' => $event->amount,
                    'purchase_rate' => $purchaseRate,
                ],
            );

            $this->awardService->execute($dto);

            Log::info('Bonus awarded on payment processed', [
                'payment_id' => $event->paymentId,
                'user_id' => $event->userId,
                'tenant_id' => $event->tenantId,
                'bonus_amount' => $bonusAmount,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to award bonus on payment processed', [
                'payment_id' => $event->paymentId,
                'user_id' => $event->userId,
                'tenant_id' => $event->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }
}
