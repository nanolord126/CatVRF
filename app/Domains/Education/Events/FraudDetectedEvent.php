<?php declare(strict_types=1);

namespace App\Domains\Education\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FraudDetectedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $fraudId,
        public readonly string $fraudType,
        public readonly string $severity,
        public readonly int $userId,
        public readonly ?int $enrollmentId,
        public readonly ?int $reviewId,
        public readonly int $tenantId,
        public readonly string $correlationId,
    ) {}
}
