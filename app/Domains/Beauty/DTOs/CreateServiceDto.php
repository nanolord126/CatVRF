<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания Service (услуги) в вертикали Beauty.
 *
 * CANON 2026 — Layer 2: DTOs.
 * Все свойства public readonly (PHP 8.3+), типизированы.
 * Включает поля: название, длительность, цена, расходники.
 *
 * @package App\Domains\Beauty\DTOs
 */
final readonly class CreateServiceDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public int $salonId,
        public ?int $masterId,
        public string $name,
        public ?string $description,
        public int $durationMinutes,
        public int $priceKopecks,
        public ?array $consumables = null,
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
            masterId: isset($validated['master_id']) ? (int) $validated['master_id'] : null,
            name: (string) ($validated['name'] ?? ''),
            description: $validated['description'] ?? null,
            durationMinutes: (int) ($validated['duration_minutes'] ?? 60),
            priceKopecks: (int) ($validated['price'] ?? 0),
            consumables: $validated['consumables'] ?? null,
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
            'salon_id' => $this->salonId,
            'master_id' => $this->masterId,
            'name' => $this->name,
            'description' => $this->description,
            'duration_minutes' => $this->durationMinutes,
            'price' => $this->priceKopecks,
            'consumables' => $this->consumables,
            'correlation_id' => $this->correlationId,
        ];
    }
}
