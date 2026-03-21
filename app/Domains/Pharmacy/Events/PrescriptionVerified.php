<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PrescriptionVerified
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $prescriptionId,
        public readonly int $tenantId,
        public readonly int $verifiedBy,
        public readonly string $correlationId,
    ) {}
}
