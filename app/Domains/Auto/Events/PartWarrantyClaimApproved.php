<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\PartWarranty;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class PartWarrantyClaimApproved
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
final class PartWarrantyClaimApproved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PartWarranty $warranty,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('Part warranty claim approved.', [
            'correlation_id' => $this->correlationId,
            'warranty_id' => $this->warranty->id,
            'tenant_id' => $this->warranty->tenant_id,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->warranty->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'warranty.part.claim.approved';
    }

    public function broadcastWith(): array
    {
        return [
            'warranty_id' => $this->warranty->id,
            'part_name' => $this->warranty->part_name,
            'status' => 'approved',
            'correlation_id' => $this->correlationId,
        ];
    }
}
