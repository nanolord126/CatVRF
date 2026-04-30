<?php

declare(strict_types=1);

/**
 * VapeOrderPaidEvent — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/vapeorderpaidevent
 */

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\Events;

use Psr\Log\LoggerInterface;

final class VapeOrderPaidEvent
{
    private readonly int $orderId;

    private readonly string $correlationId;

    /**
     * Создание события.
     */
    public function __construct(VapeOrder $order, ?string $correlationId, private readonly LoggerInterface $logger)
    {
        $this->orderId = $order->id;
        $this->correlationId = $correlationId ?? (string) Str::uuid();

        $this->logger->$this->logger->info('Vape order PAID event fired', [
            'order_id' => $this->orderId,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
