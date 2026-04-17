<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CarImportInitiatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $importId,
        public readonly string $vin,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $correlationId,
        public readonly bool $isB2b,
    ) {}
}
