<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания Appointment в вертикали Beauty.
 *
 * CANON 2026 — Layer 2: DTOs.
 * Все свойства public readonly (PHP 8.3+), типизированы.
 * Статический from() для гидрации из Request.
 * toArray() для передачи в Eloquent::create().
 *
 * @package App\Domains\Beauty\DTOs
 */
final readonly class CreateAppointmentDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public int $salonId,
        public int $masterId,
        public int $serviceId,
        public string $datetimeStart,
        public string $datetimeEnd,
        public int $priceKopecks,
        public ?string $clientComment = null,
        public ?string $idempotencyKey = null,
        public bool $isB2B = false,
    ) {
    }

    public static function from(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            tenantId: (int) tenant()?->id,
            businessGroupId: $request->input('business_group_id')
                ? (int) $request->input('business_group_id')
                : null,
            userId: (int) $request->user()?->id,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
            salonId: (int) ($validated['salon_id'] ?? 0),
            masterId: (int) ($validated['master_id'] ?? 0),
            serviceId: (int) ($validated['service_id'] ?? 0),
            datetimeStart: (string) ($validated['datetime_start'] ?? ''),
            datetimeEnd: (string) ($validated['datetime_end'] ?? ''),
            priceKopecks: (int) ($validated['price'] ?? 0),
            clientComment: $validated['client_comment'] ?? null,
            idempotencyKey: $request->header('Idempotency-Key'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getBusinessGroupId(): ?int
    {
        return $this->businessGroupId;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'salon_id' => $this->salonId,
            'master_id' => $this->masterId,
            'service_id' => $this->serviceId,
            'datetime_start' => $this->datetimeStart,
            'datetime_end' => $this->datetimeEnd,
            'price' => $this->priceKopecks,
            'client_comment' => $this->clientComment,
            'correlation_id' => $this->correlationId,
        ];
    }
}
