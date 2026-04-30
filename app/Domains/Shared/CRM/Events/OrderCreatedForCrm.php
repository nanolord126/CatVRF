<?php

declare(strict_types=1);

namespace App\Domains\CRM\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * OrderCreatedForCrm — событие создания заказа для интеграции с CRM.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class OrderCreatedForCrm
{
    use Dispatchable;

    public function __construct(
        public readonly int $orderId,
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly int $customerId,
        public readonly string $orderTitle,
        public readonly int $orderValue,
        public readonly string $vertical,
        public readonly ?string $correlationId = null,
    ) {}
}
