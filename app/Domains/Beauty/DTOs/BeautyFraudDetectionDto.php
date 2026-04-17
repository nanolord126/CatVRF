<?php

declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\Request;

final readonly class BeautyFraudDetectionDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $userId,
        public string $action,
        public ?int $appointmentId,
        public ?int $masterId,
        public ?int $amount,
        public ?string $ipAddress,
        public ?string $userAgent,
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
            action: $request->input('action'),
            appointmentId: $request->input('appointment_id') ? (int) $request->input('appointment_id') : null,
            masterId: $request->input('master_id') ? (int) $request->input('master_id') : null,
            amount: $request->input('amount') ? (int) $request->input('amount') : null,
            ipAddress: $request->ip(),
            userAgent: $request->header('User-Agent'),
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
            'action' => $this->action,
            'appointment_id' => $this->appointmentId,
            'master_id' => $this->masterId,
            'amount' => $this->amount,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
            'is_b2b' => $this->isB2B,
        ];
    }
}
