<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Domains\Auto\Models\AutoVehicle;

final class AIDiagnosticsCompletedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AutoVehicle $vehicle,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $correlationId,
        public readonly array $diagnosticsData,
    ) {}
}
