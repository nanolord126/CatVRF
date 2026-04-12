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
 * Class InsurancePolicyCreated
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
final class InsurancePolicyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly VehicleInsurance $insurance,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('InsurancePolicyCreated event dispatched', [
            'correlation_id' => $this->correlationId,
            'insurance_id' => $this->insurance->id,
            'policy_number' => $this->insurance->policy_number,
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
        return 'insurance.policy.created';
    }

    public function broadcastWith(): array
    {
        return [
            'insurance_id' => $this->insurance->id,
            'policy_number' => $this->insurance->policy_number,
            'provider' => $this->insurance->provider,
            'expires_at' => $this->insurance->expires_at->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
