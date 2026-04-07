<?php declare(strict_types=1);

namespace App\Domains\Finances\Domain\Entities;

use App\Domains\Finances\Domain\Enums\PayoutStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Domain Entity: Выплата продавцу / тенанту.
 *
 * Иммутабельный объект. Переходы статуса контролируются через PayoutStatus::canTransitionTo().
 * Все мутации только через доменные сервисы с fraud-check + DB::transaction.
 *
 * @package App\Domains\Finances\Domain\Entities
 */
final class Payout
{
    /**
     * @param int              $id            Идентификатор выплаты
     * @param int              $tenantId      Тенант-получатель
     * @param int|null         $businessGroupId Филиал (B2B)
     * @param int              $amount        Сумма в копейках
     * @param PayoutStatus     $status        Текущий статус
     * @param CarbonImmutable  $periodStart   Начало расчётного периода
     * @param CarbonImmutable  $periodEnd     Конец расчётного периода
     * @param CarbonImmutable|null $processedAt Время фактической обработки
     * @param string           $correlationId Идентификатор корреляции
     */
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly ?int $businessGroupId,
        private readonly int $amount,
        private PayoutStatus $status,
        private readonly CarbonImmutable $periodStart,
        private readonly CarbonImmutable $periodEnd,
        private ?CarbonImmutable $processedAt,
        private readonly string $correlationId,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getBusinessGroupId(): ?int
    {
        return $this->businessGroupId;
    }

    /**
     * Сумма в копейках.
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Сумма в рублях.
     */
    public function getAmountInRubles(): float
    {
        return round($this->amount / 100, 2);
    }

    public function getStatus(): PayoutStatus
    {
        return $this->status;
    }

    public function getPeriodStart(): CarbonImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): CarbonImmutable
    {
        return $this->periodEnd;
    }

    public function getProcessedAt(): ?CarbonImmutable
    {
        return $this->processedAt;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Переход в новый статус с валидацией машины состояний.
     *
     * @throws \DomainException Если переход невозможен.
     */
    public function transitionTo(PayoutStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Невозможен переход выплаты #{$this->id} из {$this->status->value} в {$newStatus->value}"
            );
        }

        $this->status = $newStatus;

        if ($newStatus === PayoutStatus::COMPLETED) {
            $this->processedAt = CarbonImmutable::createFromTimestamp(Carbon::now()->getTimestamp());
        }
    }

    /**
     * Завершена ли выплата (терминальный статус).
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Массив для сериализации / логирования.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'amount'            => $this->amount,
            'amount_rubles'     => $this->getAmountInRubles(),
            'status'            => $this->status->value,
            'period_start'      => $this->periodStart->toDateString(),
            'period_end'        => $this->periodEnd->toDateString(),
            'processed_at'      => $this->processedAt?->toIso8601String(),
            'correlation_id'    => $this->correlationId,
        ];
    }
}
