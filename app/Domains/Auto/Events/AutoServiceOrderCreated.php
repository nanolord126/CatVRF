<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoServiceOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AutoServiceOrderCreated
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Domains\Auto\Events
 */
final class AutoServiceOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly AutoServiceOrder $order,
        public readonly string $correlationId
    ) {

    }

    public function broadcastOn(): array
    {
        return [
            new Channel('auto.service-orders.' . $this->order->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AutoServiceOrderCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'service_type' => $this->order->service_type,
            'total_price' => $this->order->total_price,
            'created_at' => $this->order->created_at?->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
