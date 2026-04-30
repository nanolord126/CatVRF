<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\Models\InsiderThreatLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class InsiderAnomalyDetected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly User $staff,
        public readonly Tenant $tenant,
        public readonly string $actionType,
        public readonly float $anomalyScore,
        public readonly string $severity,
        public readonly InsiderThreatLog $log,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [];
    }
}
