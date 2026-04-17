<?php

declare(strict_types=1);

namespace App\Domains\Payment\Events;

use App\Domains\Payment\Models\PaymentRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: платёжная запись обновлена (смена статуса).
 */
final class PaymentRecordUpdated
{

    /**
     * @param PaymentRecord        $paymentRecord текущее состояние
     * @param string               $correlationId correlation_id
     * @param array<string, mixed> $oldValues     значения до изменения
     * @param array<string, mixed> $newValues     значения после изменения
     * @param int|null             $userId        кто инициировал
     */
    public function __construct(
        public readonly PaymentRecord $paymentRecord,
        public readonly string $correlationId,
        public readonly array $oldValues = [],
        public readonly array $newValues = [],
        public readonly ?int $userId = null,
    ) {}

    /**
     * Изменилось ли конкретное поле.
     */
    public function hasChanged(string $field): bool
    {
        return array_key_exists($field, $this->newValues);
    }

    /**
     * Контекст для аудита.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'event' => 'payment_record_updated',
            'payment_record_id' => $this->paymentRecord->id,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
            'correlation_id' => $this->correlationId,
            'user_id' => $this->userId,
        ];
    }

    /**
     * Tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->paymentRecord->tenant_id ?? null;
    }

    /**
     * Business group ID.
     */
    public function getBusinessGroupId(): ?int
    {
        return $this->paymentRecord->business_group_id ?? null;
    }
}
