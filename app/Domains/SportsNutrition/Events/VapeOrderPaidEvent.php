<?php declare(strict_types=1);

/**
 * VapeOrderPaidEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/vapeorderpaidevent
 */


namespace App\Domains\SportsNutrition\Events;


use Psr\Log\LoggerInterface;
final class VapeOrderPaidEvent
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        private int $orderId;
        private string $correlationId;

        /**
         * Создание события.
         */
        public function __construct(VapeOrder $order, string $correlationId = null, private readonly LoggerInterface $logger)
        {
            $this->orderId = $order->id;
            $this->correlationId = $correlationId ?? (string) Str::uuid();

            $this->logger->info('Vape order PAID event fired', [
                'order_id' => $this->orderId,
                'correlation_id' => $this->correlationId,
            ]);
        }
}
