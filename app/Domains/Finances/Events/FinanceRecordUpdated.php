<?php

declare(strict_types=1);

namespace App\Domains\Finances\Events;

use App\Domains\Finances\Models\FinanceRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие обновления финансовой записи.
 *
 * Диспатчится после успешного обновления FinanceRecord.
 * Несёт модель, старые значения и correlation_id.
 * Обрабатывается LogFinanceRecordUpdated лисенером.
 *
 * @package App\Domains\Finances\Events
 */
final class FinanceRecordUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param FinanceRecord  $financeRecord Обновлённая модель
     * @param string         $correlationId Сквозной идентификатор
     * @param array          $oldValues     Старые значения до обновления
     * @param array          $newValues     Новые значения после обновления
     * @param int|null       $userId        Пользователь-инициатор
     */
    public function __construct(
        public readonly FinanceRecord $financeRecord,
        public readonly string $correlationId,
        public readonly array $oldValues = [],
        public readonly array $newValues = [],
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
            'old_values'        => $this->oldValues,
            'new_values'        => $this->newValues,
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

    /**
     * Проверить, изменилось ли конкретное поле.
     */
    public function hasChanged(string $field): bool
    {
        return array_key_exists($field, $this->newValues);
    }
}
