<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;

final readonly class VideoCallDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public int $masterId,
        public ?string $scheduledFor,
        public ?int $durationMinutes,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?bool $isB2B = null,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            businessGroupId: $request->has('inn') && $request->has('business_card_id')
                ? (int) $request->input('business_card_id')
                : null,
            userId: (int) $request->input('user_id'),
            masterId: (int) $request->input('master_id'),
            scheduledFor: $request->input('scheduled_for'),
            durationMinutes: $request->input('duration_minutes') ? (int) $request->input('duration_minutes') : null,
            correlationId: $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            isB2B: $request->has('inn') && $request->has('business_card_id'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'user_id' => $this->userId,
            'master_id' => $this->masterId,
            'scheduled_for' => $this->scheduledFor,
            'duration_minutes' => $this->durationMinutes,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'is_b2b' => $this->isB2B,
        ];
    }
}
