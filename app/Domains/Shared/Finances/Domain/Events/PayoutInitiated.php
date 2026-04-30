<?php

declare(strict_types=1);

namespace App\Domains\Finances\Domain\Events;

use Illuminate\Database\DatabaseManager;

/**
 * Domain Event: выплата инициирована.
 *
 * Диспатчится после успешного создания Payout в $this->db->transaction.
 * Слушатели: уведомление тенанту, запись в ClickHouse, security monitoring.
 */
final class PayoutInitiated
{
    /**
     * @param  int  $tenantId  Тенант-получатель
     * @param  int|null  $businessGroupId  Филиал (B2B)
     * @param  int  $amount  Сумма в копейках
     * @param  string  $periodStart  Начало периода (ISO 8601)
     * @param  string  $periodEnd  Конец периода (ISO 8601)
     * @param  string  $correlationId  Идентификатор корреляции
     */
    public function __construct(private readonly DatabaseManager $db,
        public readonly int $tenantId,
        public readonly ?int $businessGroupId,
        public readonly int $amount,
        public readonly string $periodStart,
        public readonly string $periodEnd,
        public readonly string $correlationId,) {}

    /**
     * Сумма в рублях (для логов и уведомлений).
     */
    public function getAmountInRubles(): float
    {
        return round($this->amount / 100, 2);
    }

    /**
     * Контекст для audit-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'amount'            => $this->amount,
            'amount_rubles'     => $this->getAmountInRubles(),
            'period_start'      => $this->periodStart,
            'period_end'        => $this->periodEnd,
            'correlation_id'    => $this->correlationId,
        ];
    }
}
