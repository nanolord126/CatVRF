<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Events;

use App\Domains\Bonuses\DTOs\SpendBonusDto;
use App\Domains\Bonuses\Enums\BonusStatus;
use App\Domains\Bonuses\Enums\BonusType;
use App\Domains\Bonuses\Models\BonusTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: бонус потрачен.
 *
 * Диспетчится при успешной трате бонусов пользователем.
 * Используется для интеграции с Audit, BigData, RFM обновлением.
 */
final class BonusSpent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly BonusTransaction $transaction,
        public readonly SpendBonusDto $dto,
    ) {}

    /**
     * Получить ID транзакции.
     */
    public function getTransactionId(): int
    {
        return $this->transaction->id;
    }

    /**
     * Получить UUID транзакции.
     */
    public function getTransactionUuid(): string
    {
        return $this->transaction->uuid;
    }

    /**
     * Получить ID пользователя.
     */
    public function getUserId(): int
    {
        return $this->transaction->user_id;
    }

    /**
     * Получить ID тенанта.
     */
    public function getTenantId(): int
    {
        return $this->transaction->tenant_id;
    }

    /**
     * Получить сумму потраченных бонусов.
     */
    public function getAmount(): int
    {
        return abs($this->transaction->amount);
    }

    /**
     * Получить тип бонуса.
     */
    public function getType(): BonusType
    {
        return $this->transaction->type;
    }

    /**
     * Получить статус бонуса.
     */
    public function getStatus(): BonusStatus
    {
        return $this->transaction->status;
    }

    /**
     * Получить correlation ID.
     */
    public function getCorrelationId(): string
    {
        return $this->dto->correlationId;
    }

    /**
     * Получить источник траты.
     */
    public function getSourceType(): ?string
    {
        return $this->dto->sourceType;
    }

    /**
     * Получить ID источника.
     */
    public function getSourceId(): ?int
    {
        return $this->dto->sourceId;
    }

    /**
     * Преобразовать в массив для логирования/BigData.
     */
    public function toArray(): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'transaction_uuid' => $this->transaction->uuid,
            'user_id' => $this->transaction->user_id,
            'tenant_id' => $this->transaction->tenant_id,
            'amount' => abs($this->transaction->amount),
            'type' => $this->transaction->type->value,
            'status' => $this->transaction->status->value,
            'source_type' => $this->dto->sourceType,
            'source_id' => $this->dto->sourceId,
            'reason' => $this->dto->reason,
            'correlation_id' => $this->dto->correlationId,
        ];
    }
}
