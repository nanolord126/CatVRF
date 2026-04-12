<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\VehicleInsurance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class InsurancePolicyExpiring
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Domain event dispatched after a significant action.
 * Events carry correlation_id for full traceability.
 * Listeners handle side effects asynchronously.
 *
 * @see \Illuminate\Foundation\Events\Dispatchable
 * @package App\Domains\Auto\Events
 */
final class InsurancePolicyExpiring implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly VehicleInsurance $insurance,
        public readonly int $daysUntilExpiry,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('InsurancePolicyExpiring event dispatched', [
            'correlation_id' => $this->correlationId,
            'insurance_id' => $this->insurance->id,
            'days_until_expiry' => $this->daysUntilExpiry,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->insurance->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'insurance.policy.expiring';
    }

    public function broadcastWith(): array
    {
        return [
            'insurance_id' => $this->insurance->id,
            'policy_number' => $this->insurance->policy_number,
            'expires_at' => $this->insurance->expires_at->toIso8601String(),
            'days_until_expiry' => $this->daysUntilExpiry,
            'correlation_id' => $this->correlationId,
        ];
    }
}
