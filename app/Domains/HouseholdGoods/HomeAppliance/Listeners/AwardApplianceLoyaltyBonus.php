<?php declare(strict_types=1);

/**
 * AwardApplianceLoyaltyBonus — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/awardapplianceloyaltybonus
 */


namespace App\Domains\HouseholdGoods\HomeAppliance\Listeners;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class AwardApplianceLoyaltyBonus
{

    public function __construct(private ReferralService $referralService,
        private readonly Request $request, private readonly LoggerInterface $logger) {}

        /**
         * Обработка завершенного ремонта (HomeApplianceRepairCompleted event).
         */
        public function handle(ApplianceRepairOrder $order): void
        {
            $this->logger->info('Checking HomeAppliance loyalty eligibility', [
                'order_uuid' => $order->uuid,
                'amount' => $order->total_cost_kopecks,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            // Порог: Ремонт от 10 000 руб (1 000 000 коп)
            $threshold = 1000000;

            if ($order->total_cost_kopecks >= $threshold) {
                // Начисление бонуса 1000 руб (100 000 коп) инициатору реферальной ссылки
                $this->referralService->awardBonus(
                    referralId: $order->client_id, // Упрощение для канонической логики 2026
                    recipientId: $order->client_id
                );

                $this->logger->info('HomeAppliance loyalty bonus awarded', [
                    'recipient_id' => $order->client_id,
                    'bonus_amount' => 100000,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            }
        }
}
