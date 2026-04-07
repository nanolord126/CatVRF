<?php declare(strict_types=1);

namespace App\Domains\Finances\Domain\Entities;

use App\Domains\Finances\Domain\Enums\TransactionType;
use Carbon\CarbonImmutable;

/**
 * Domain Entity: Финансовая транзакция.
 *
 * Иммутабельный объект, представляющий одну запись в balance_transactions.
 * Не привязан к Eloquent — чистая доменная модель.
 *
 * @package App\Domains\Finances\Domain\Entities
 */
final class FinancialTransaction
{
    /**
     * @param int             $id            Идентификатор записи
     * @param int             $tenantId      Идентификатор тенанта
     * @param int|null        $businessGroupId Идентификатор филиала (B2B)
     * @param int             $walletId      Идентификатор кошелька
     * @param TransactionType $type          Тип операции
     * @param int             $amount        Сумма в копейках (всегда > 0)
     * @param array           $metadata      Дополнительные данные операции
     * @param CarbonImmutable $createdAt     Время создания
     * @param string          $correlationId Идентификатор корреляции для трассировки
     */
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly ?int $businessGroupId,
        private readonly int $walletId,
        private readonly TransactionType $type,
        private readonly int $amount,
        private readonly array $metadata,
        private readonly CarbonImmutable $createdAt,
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

    public function getWalletId(): int
    {
        return $this->walletId;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    /**
     * Сумма в копейках (всегда положительная).
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * Сумма в рублях с точностью 2 знака.
     */
    public function getAmountInRubles(): float
    {
        return round($this->amount / 100, 2);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): CarbonImmutable
    {
        return $this->createdAt;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Является ли транзакция приходной (увеличивает баланс).
     */
    public function isCredit(): bool
    {
        return $this->type->isCredit();
    }

    /**
     * Является ли транзакция расходной (уменьшает баланс).
     */
    public function isDebit(): bool
    {
        return $this->type->isDebit();
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
            'wallet_id'         => $this->walletId,
            'type'              => $this->type->value,
            'amount'            => $this->amount,
            'amount_rubles'     => $this->getAmountInRubles(),
            'metadata'          => $this->metadata,
            'correlation_id'    => $this->correlationId,
            'created_at'        => $this->createdAt->toIso8601String(),
        ];
    }
}
