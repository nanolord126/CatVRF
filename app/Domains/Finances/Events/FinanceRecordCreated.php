<?php

declare(strict_types=1);

namespace App\Domains\Finances\Events;

use App\Domains\Finances\Models\FinanceRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания финансовой записи.
 *
 * Диспатчится после успешного создания FinanceRecord.
 * Несёт модель и correlation_id для трассировки.
 * Обрабатывается LogFinanceRecordCreated лисенером.
 *
 * @package App\Domains\Finances\Events
 */
final class FinanceRecordCreated
{

    /**
     * @param FinanceRecord $financeRecord Созданная модель
     * @param string        $correlationId Сквозной идентификатор
     * @param int|null      $userId        Пользователь-инициатор
     */
    public function __construct(
        public readonly FinanceRecord $financeRecord,
        public readonly string $correlationId,
        public readonly ?int $userId = null,
    ) {}

    /**
     * Контекст для аудит-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'finance_record_id' => $this->financeRecord->id,
            'tenant_id'         => $this->financeRecord->tenant_id ?? null,
            'business_group_id' => $this->financeRecord->business_group_id ?? null,
            'type'              => $this->financeRecord->type ?? null,
            'amount'            => $this->financeRecord->amount ?? null,
            'correlation_id'    => $this->correlationId,
            'user_id'           => $this->userId,
        ];
    }

    /**
     * Получить tenant_id из модели.
     */
    public function getTenantId(): ?int
    {
        return $this->financeRecord->tenant_id ?? null;
    }

    /**
     * Получить business_group_id из модели.
     */
    public function getBusinessGroupId(): ?int
    {
        return $this->financeRecord->business_group_id ?? null;
    }
}
