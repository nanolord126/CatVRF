<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class EscrowReleaseDto
{
    public function __construct(
        public int $tenantId,
        public string $transactionUuid,
        public string $correlationId,
        public string $reason,
        public string $releasedBy,
        public ?string $releaseNotes,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            transactionUuid: $request->input('transaction_uuid'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            reason: $request->input('reason'),
            releasedBy: $request->input('released_by'),
            releaseNotes: $request->input('release_notes'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'transaction_uuid' => $this->transactionUuid,
            'correlation_id' => $this->correlationId,
            'reason' => $this->reason,
            'released_by' => $this->releasedBy,
            'release_notes' => $this->releaseNotes,
        ];
    }
}
