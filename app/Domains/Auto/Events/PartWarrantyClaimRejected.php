<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\PartWarranty;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class PartWarrantyClaimRejected
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
final class PartWarrantyClaimRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    private string $correlationId;

    public function __construct(
        public readonly PartWarranty $warranty,
        public readonly string $rejectionReason,
        string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->correlationId = $correlationId;

        $this->logger->info('PartWarrantyClaimRejected event dispatched', [
            'correlation_id' => $this->correlationId,
            'warranty_id' => $this->warranty->id,
            'warranty_number' => $this->warranty->warranty_number,
            'rejection_reason' => $this->rejectionReason,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->warranty->tenant_id),
            new PrivateChannel('user.' . $this->warranty->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'warranty.part.claim.rejected';
    }
}
