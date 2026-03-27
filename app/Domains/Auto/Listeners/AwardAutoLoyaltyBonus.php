<?php

declare(strict_types=1);

namespace App\Domains\Auto\Listeners;

use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Referral\Services\ReferralService;
use Illuminate\Support\Facades\Log;

/**
 * AwardAutoLoyaltyBonus — Канон 2026.
 * Начисляет бонусы клиенту в WalletService при завершении крупного ремонта или ТО.
 */
final readonly class AwardAutoLoyaltyBonus
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    /**
     * Обработка завершенного заказ-наряда (AutoRepairOrderCompleted event).
     */
    public function handle(AutoRepairOrder $order): void
    {
        Log::channel('referral')->info('Auto Loyalty processing started', [
            'order_uuid' => $order->uuid,
            'total_cost' => $order->total_cost_kopecks,
        ]);

        // 1. Порог для реферальной квалификации (напр. 50 000 руб)
        $threshold = 5000000; // 50 000 руб в коп.

        if ($order->total_cost_kopecks >= $threshold) {
            // 2. Начислить бонус рефереру или самому клиенту по правилам 2026
            $this->referralService->awardBonus(
                referralId: $order->client_id, // Упрощенно: ID реферала
                recipientId: $order->client_id
            );

            Log::channel('referral')->info('Auto Loyalty bonus awarded', [
                'client_id' => $order->client_id,
                'amount_rub' => 2000, // Пример из канона
            ]);
        }
    }
}
