<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class AutoPartOrderCreated
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
final class AutoPartOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $autoPartOrderId,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $totalPrice,
        public readonly string $correlationId, public readonly LoggerInterface $logger) {
        $this->logger->info('AutoPartOrderCreated event dispatched', [
            'correlation_id' => $this->correlationId,
            'order_id' => $this->autoPartOrderId,
            'tenant_id' => $this->tenantId,
            'total_price' => $this->totalPrice,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->tenantId}.auto.orders"),
            new PrivateChannel("user.{$this->userId}.orders"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'auto.part.order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->autoPartOrderId,
            'total_price' => $this->totalPrice,
            'correlation_id' => $this->correlationId,
        ];
    }
}
