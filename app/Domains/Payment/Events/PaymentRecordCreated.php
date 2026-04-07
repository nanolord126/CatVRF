<?php

declare(strict_types=1);

namespace App\Domains\Payment\Events;

use App\Domains\Payment\Models\PaymentRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: платёжная запись создана.
 *
 * Все свойства — public readonly, чтобы листенеры
 * имели доступ без геттеров.
 */
final class PaymentRecordCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly PaymentRecord $paymentRecord,
        public readonly string $correlationId,
        public readonly ?int $userId = null,
    ) {}

    /**
     * Контекст для аудита.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'event' => 'payment_record_created',
            'payment_record_id' => $this->paymentRecord->id,
            'provider_code' => $this->paymentRecord->provider_code?->value ?? 'unknown',
            'amount_kopecks' => $this->paymentRecord->amount_kopecks,
            'status' => $this->paymentRecord->status?->value ?? 'unknown',
            'correlation_id' => $this->correlationId,
            'user_id' => $this->userId,
        ];
    }

    /**
     * Tenant ID из платёжной записи.
     */
    public function getTenantId(): ?int
    {
        return $this->paymentRecord->tenant_id ?? null;
    }

    /**
     * Business group ID из платёжной записи.
     */
    public function getBusinessGroupId(): ?int
    {
        return $this->paymentRecord->business_group_id ?? null;
    }
}
