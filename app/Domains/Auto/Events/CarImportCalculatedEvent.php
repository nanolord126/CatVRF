<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CarImportCalculatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $vin,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $correlationId,
        public readonly array $calculationData,
    ) {}
}
