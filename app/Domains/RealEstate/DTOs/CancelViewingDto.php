<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class CancelViewingDto
{
    public function __construct(
        public int $tenantId,
        public string $viewingUuid,
        public int $buyerId,
        public string $correlationId,
        public string $reason,
        public string $cancelledBy,
        public ?string $cancellationNotes,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: (int) $request->header('X-Tenant-ID'),
            viewingUuid: $request->input('viewing_uuid'),
            buyerId: (int) $request->input('buyer_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
            reason: $request->input('reason'),
            cancelledBy: $request->input('cancelled_by'),
            cancellationNotes: $request->input('cancellation_notes'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'viewing_uuid' => $this->viewingUuid,
            'buyer_id' => $this->buyerId,
            'correlation_id' => $this->correlationId,
            'reason' => $this->reason,
            'cancelled_by' => $this->cancelledBy,
            'cancellation_notes' => $this->cancellationNotes,
        ];
    }
}
