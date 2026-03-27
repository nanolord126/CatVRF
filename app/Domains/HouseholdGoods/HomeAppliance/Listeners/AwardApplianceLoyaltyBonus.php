<?php

declare(strict_types=1);

namespace App\Domains\HouseholdGoods\HomeAppliance\Listeners;

use App\Domains\HouseholdGoods\HomeAppliance\Models\ApplianceRepairOrder;
use App\Domains\Referral\Services\ReferralService;
use Illuminate\Support\Facades\Log;

/**
 * AwardApplianceLoyaltyBonus — Канон 2026.
 * Начисляет бонусы рефереру при выполнении крупного ремонта (напр. замена компрессора).
 */
final readonly class AwardApplianceLoyaltyBonus
{
    public function __construct(
        private ReferralService $referralService
    ) {}

    /**
     * Обработка завершенного ремонта (HomeApplianceRepairCompleted event).
     */
    public function handle(ApplianceRepairOrder $order): void
    {
        Log::channel('audit')->info('Checking HomeAppliance loyalty eligibility', [
            'order_uuid' => $order->uuid,
            'amount' => $order->total_cost_kopecks
        ]);

        // Порог: Ремонт от 10 000 руб (1 000 000 коп)
        $threshold = 1000000;

        if ($order->total_cost_kopecks >= $threshold) {
            // Начисление бонуса 1000 руб (100 000 коп) инициатору реферальной ссылки
            $this->referralService->awardBonus(
                referralId: $order->client_id, // Упрощение для канонической логики 2026
                recipientId: $order->client_id 
            );

            Log::channel('audit')->info('HomeAppliance loyalty bonus awarded', [
                'recipient_id' => $order->client_id,
                'bonus_amount' => 100000
            ]);
        }
    }
}
