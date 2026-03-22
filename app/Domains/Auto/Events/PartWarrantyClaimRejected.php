<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\PartWarranty;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PartWarrantyClaimRejected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly PartWarranty $warranty,
        public readonly string $rejectionReason,
        public readonly string $correlationId
    ) {
        Log::channel('audit')->info('PartWarrantyClaimRejected event dispatched', [
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
