<?php

declare(strict_types=1);

namespace App\Domains\Finances\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания Budget в вертикали Finances.
 *
 * Immutable value object. Создаётся из Request или напрямую.
 * Несёт все обязательные поля: tenant_id, business_group_id,
 * correlation_id, idempotency_key и данные бюджета.
 *
 * @package App\Domains\Finances\DTOs
 */
final readonly class CreateBudgetDto
{
    /**
     * @param int         $tenantId        Идентификатор текущего тенанта
     * @param int|null    $businessGroupId Идентификатор бизнес-группы (B2B)
     * @param int         $userId          Идентификатор пользователя-создателя
     * @param string      $correlationId   Сквозной идентификатор трассировки
     * @param string      $name            Название бюджета
     * @param int         $amount          Сумма бюджета в копейках
     * @param string      $currency        Валюта бюджета (RUB, USD и т.д.)
     * @param string      $periodStart     Начало периода (Y-m-d)
     * @param string      $periodEnd       Конец периода (Y-m-d)
     * @param array       $metadata        Дополнительные данные
     * @param string|null $idempotencyKey  Ключ идемпотентности
     * @param bool        $isB2B           Флаг B2B-операции
     */
    public function __construct(
        public int     $tenantId,
        public ?int    $businessGroupId,
        public int     $userId,
        public string  $correlationId,
        public string  $name,
        public int     $amount,
        public string  $currency = 'RUB',
        public string  $periodStart = '',
        public string  $periodEnd = '',
        public array   $metadata = [],
        public ?string $idempotencyKey = null,
        public bool    $isB2B = false,
    ) {}

    /**
     * Создать DTO из HTTP-запроса.
     *
     * B2B определяется по наличию inn + business_card_id.
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
            name:            (string) ($validated['name'] ?? ''),
            amount:          (int) ($validated['amount'] ?? 0),
            currency:        (string) ($validated['currency'] ?? 'RUB'),
            periodStart:     (string) ($validated['period_start'] ?? ''),
            periodEnd:       (string) ($validated['period_end'] ?? ''),
            metadata:        (array) ($validated['metadata'] ?? []),
            idempotencyKey:  $request->header('Idempotency-Key'),
            isB2B:           $request->has('inn') && $request->has('business_card_id'),
        );
    }

    /**
     * Преобразовать DTO в массив для сохранения в БД.
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
            'name'              => $this->name,
            'amount'            => $this->amount,
            'currency'          => $this->currency,
            'period_start'      => $this->periodStart,
            'period_end'        => $this->periodEnd,
            'metadata'          => $this->metadata,
        ];
    }

    /**
     * Получить сумму бюджета в рублях (из копеек).
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
            'name'              => $this->name,
            'amount'            => $this->amount,
            'is_b2b'            => $this->isB2B,
        ];
    }
}
