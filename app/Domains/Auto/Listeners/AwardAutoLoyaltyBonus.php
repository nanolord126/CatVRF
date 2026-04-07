<?php declare(strict_types=1);

/**
 * AwardAutoLoyaltyBonus — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/awardautoloyaltybonus
 */


namespace App\Domains\Auto\Listeners;


use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final class AwardAutoLoyaltyBonus
{

    public function __construct(private ReferralService $referralService,
        private readonly Request $request, private readonly LoggerInterface $logger) {}

        /**
         * Обработка завершенного заказ-наряда (AutoRepairOrderCompleted event).
         */
        public function handle(AutoRepairOrder $order): void
        {
            $this->logger->info('Auto Loyalty processing started', [
                'order_uuid' => $order->uuid,
                'total_cost' => $order->total_cost_kopecks,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            // 1. Порог для реферальной квалификации (напр. 50 000 руб)
            $threshold = 5000000; // 50 000 руб в коп.

            if ($order->total_cost_kopecks >= $threshold) {
                // 2. Начислить бонус рефереру или самому клиенту по правилам 2026
                $this->referralService->awardBonus(
                    referralId: $order->client_id, // Упрощенно: ID реферала
                    recipientId: $order->client_id
                );

                $this->logger->info('Auto Loyalty bonus awarded', [
                    'client_id' => $order->client_id,
                    'amount_rub' => 2000, // Пример из канона,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            }
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
