<?php declare(strict_types=1);

namespace App\Domains\Fashion\DTOs;

use Carbon\Carbon;

final readonly class FashionWebRTCSessionDto
{
    public function __construct(
        public string $sessionId,
        public int $userId,
        public int $stylistId,
        public int $tenantId,
        public ?int $businessGroupId,
        public string $sessionToken,
        public string $status,
        public Carbon $scheduledAt,
        public Carbon $expiresAt,
        public string $webrtcUrl,
        public string $correlationId,
    ) {}

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'stylist_id' => $this->stylistId,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'session_token' => $this->sessionToken,
            'status' => $this->status,
            'scheduled_at' => $this->scheduledAt->toIso8601String(),
            'expires_at' => $this->expiresAt->toIso8601String(),
            'webrtc_url' => $this->webrtcUrl,
            'correlation_id' => $this->correlationId,
        ];
    }
}
