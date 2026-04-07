<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * DTO для создания Master в вертикали Beauty.
 *
 * CANON 2026 — Layer 2: DTOs.
 * Все свойства public readonly (PHP 8.3+), типизированы.
 * Включает валидированные поля мастера: ФИО, специализация, опыт.
 *
 * @package App\Domains\Beauty\DTOs
 */
final readonly class CreateMasterDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $correlationId,
        public int $salonId,
        public string $fullName,
        public array $specialization,
        public int $experienceYears,
        public ?string $bio = null,
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
            fullName: (string) ($validated['full_name'] ?? ''),
            specialization: (array) ($validated['specialization'] ?? []),
            experienceYears: (int) ($validated['experience_years'] ?? 0),
            bio: $validated['bio'] ?? null,
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
            'full_name' => $this->fullName,
            'specialization' => $this->specialization,
            'experience_years' => $this->experienceYears,
            'bio' => $this->bio,
            'correlation_id' => $this->correlationId,
        ];
    }
}
