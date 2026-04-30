<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\InsiderThreatLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Insider Anomaly Detected Event
 * 
 * Dispatched when the InsiderThreatService detects suspicious behavior
 * from a staff member accessing client data.
 * 
 * PRODUCTION MANDATORY — CatVRF 2026 Data Exfiltration Fortress.
 */
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
}
