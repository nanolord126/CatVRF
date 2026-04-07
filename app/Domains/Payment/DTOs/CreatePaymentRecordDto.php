<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use Illuminate\Http\Request;

/**
 * DTO для инициализации платежа.
 *
 * Все суммы в копейках. idempotencyKey — обязательный ключ
 * для предотвращения двойных списаний.
 */
final readonly class CreatePaymentRecordDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $providerCode,
        public int $amountKopecks,
        public string $idempotencyKey,
        public string $correlationId,
        public bool $isHold = false,
        public string $description = '',
        public ?array $metadata = null,
    ) {}

    /**
     * Создать из HTTP-запроса.
     */
    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) (function_exists('tenant') && tenant() ? tenant()->id : $request->input('tenant_id', 0)),
            businessGroupId: $request->has('business_group_id') ? (int) $request->input('business_group_id') : null,
            providerCode: (string) $request->input('provider_code', 'tinkoff'),
            amountKopecks: (int) $request->input('amount_kopecks', 0),
            idempotencyKey: (string) $request->input('idempotency_key', ''),
            correlationId: (string) ($request->header('X-Correlation-ID') ?? $request->input('correlation_id', '')),
            isHold: (bool) $request->input('is_hold', false),
            description: (string) $request->input('description', ''),
            metadata: $request->input('metadata'),
        );
    }

    /**
     * Массив для сохранения в БД.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'provider_code' => $this->providerCode,
            'amount_kopecks' => $this->amountKopecks,
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'is_hold' => $this->isHold,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Контекст для аудит-лога.
     *
     * @return array<string, mixed>
     */
    public function toAuditContext(): array
    {
        return [
            'provider_code' => $this->providerCode,
            'amount_kopecks' => $this->amountKopecks,
            'idempotency_key' => $this->idempotencyKey,
            'is_hold' => $this->isHold,
            'correlation_id' => $this->correlationId,
        ];
    }
}
