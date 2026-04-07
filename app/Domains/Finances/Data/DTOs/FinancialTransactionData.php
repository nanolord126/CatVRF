<?php

declare(strict_types=1);

namespace App\Domains\Finances\Data\DTOs;

use App\Domains\Finances\Domain\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Spatie Data Transfer Object для финансовой транзакции.
 *
 * Используется для сериализации/десериализации в Spatie
 * pipeline (валидация, cast, преобразование). Несёт все поля
 * транзакции включая correlation_id и tenant scoping.
 *
 * @package App\Domains\Finances\Data\DTOs
 */
final class FinancialTransactionData extends Data
{
    /**
     * @param int             $id            Идентификатор
     * @param int             $tenantId      Тенант
     * @param int|null        $businessGroupId Бизнес-группа
     * @param int             $walletId      Кошелёк
     * @param TransactionType $type          Тип транзакции
     * @param int             $amount        Сумма в копейках
     * @param string          $correlationId Correlation ID
     * @param array           $metadata      Доп. данные
     * @param CarbonImmutable $createdAt     Дата создания
     */
    public function __construct(
        public readonly int             $id,
        public readonly int             $tenantId,
        public readonly ?int            $businessGroupId,
        public readonly int             $walletId,
        public readonly TransactionType $type,
        public readonly int             $amount,
        public readonly string          $correlationId,
        public readonly array           $metadata,
        public readonly CarbonImmutable $createdAt,
    ) {}

    /**
     * Получить сумму в рублях (из копеек).
     */
    public function getAmountInRubles(): float
    {
        return $this->amount / 100;
    }

    /**
     * Проверить, является ли транзакция кредитовой (пополнение).
     */
    public function isCredit(): bool
    {
        return $this->type->isCredit();
    }

    /**
     * Проверить, является ли транзакция дебетовой (списание).
     */
    public function isDebit(): bool
    {
        return $this->type->isDebit();
    }

    /**
     * Контекст для аудит-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'id'                => $this->id,
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'wallet_id'         => $this->walletId,
            'type'              => $this->type->value,
            'amount'            => $this->amount,
            'correlation_id'    => $this->correlationId,
        ];
    }

    /**
     * Создать Data-объект из Eloquent-модели FinanceRecord.
     *
     * @param \App\Domains\Finances\Models\FinanceRecord $model
     */
    public static function fromModel(\App\Domains\Finances\Models\FinanceRecord $model): self
    {
        return new self(
            id:              $model->id,
            tenantId:        $model->tenant_id,
            businessGroupId: $model->business_group_id,
            walletId:        $model->wallet_id ?? 0,
            type:            TransactionType::tryFrom($model->type ?? '') ?? TransactionType::DEPOSIT,
            amount:          (int) ($model->amount ?? 0),
            correlationId:   $model->correlation_id ?? '',
            metadata:        is_array($model->metadata) ? $model->metadata : [],
            createdAt:       CarbonImmutable::parse($model->created_at),
        );
    }
}
