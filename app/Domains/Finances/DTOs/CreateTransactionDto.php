<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use App\Domains\Finances\Domain\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания финансовой транзакции.
 *
 * Immutable value object. Включает wallet_id, тип транзакции,
 * сумму (в копейках), метаданные и все обязательные поля канона.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class CreateTransactionDto
{
    /**
     * @param int              $tenantId        Идентификатор тенанта
     * @param int|null         $businessGroupId Идентификатор бизнес-группы (B2B)
     * @param int              $userId          Идентификатор пользователя
     * @param string           $correlationId   Сквозной correlation ID
     * @param int              $walletId        Идентификатор кошелька
     * @param TransactionType  $type            Тип транзакции (deposit, withdrawal, commission и т.д.)
     * @param int              $amount          Сумма в копейках (всегда > 0)
     * @param string           $description     Описание операции
     * @param array            $metadata        Произвольные метаданные
     * @param string|null      $idempotencyKey  Ключ идемпотентности
     * @param bool             $isB2B           Флаг B2B-операции
     */
    public function __construct(
        public int             $tenantId,
        public ?int            $businessGroupId,
        public int             $userId,
        public string          $correlationId,
        public int             $walletId,
        public TransactionType $type,
        public int             $amount,
        public string          $description = '',
        public array           $metadata = [],
        public ?string         $idempotencyKey = null,
        public bool            $isB2B = false,
    ) {}

    /**
     * Создать DTO из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            tenantId:        (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            userId:          (int) $request->user()?->id,
            correlationId:   (string) $request->header(
                'X-Correlation-ID',
                Str::uuid()->toString(),
            ),
            walletId:        (int) ($validated['wallet_id'] ?? 0),
            type:            TransactionType::from((string) ($validated['type'] ?? 'deposit')),
            amount:          (int) ($validated['amount'] ?? 0),
            description:     (string) ($validated['description'] ?? ''),
            metadata:        (array) ($validated['metadata'] ?? []),
            idempotencyKey:  $request->header('Idempotency-Key'),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Преобразовать в массив для сохранения.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
            'wallet_id'         => $this->walletId,
            'type'              => $this->type->value,
            'amount'            => $this->amount,
            'description'       => $this->description,
            'metadata'          => $this->metadata,
        ];
    }

    /**
     * Получить сумму в рублях.
     */
    public function getAmountInRubles(): float
    {
        return $this->amount / 100;
    }

    /**
     * Контекст для аудит-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id'           => $this->userId,
            'correlation_id'    => $this->correlationId,
            'wallet_id'         => $this->walletId,
            'type'              => $this->type->value,
            'amount'            => $this->amount,
            'is_b2b'            => $this->isB2B,
        ];
    }
}
