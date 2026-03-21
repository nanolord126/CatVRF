<?php declare(strict_types=1);

namespace App\Domains\Electronics\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WarrantyClaimSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $warrantyClaimId,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly string $correlationId,
    ) {}
}
