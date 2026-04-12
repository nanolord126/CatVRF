<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\CarDetailing;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
/**
 * Class DetailingCompleted
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
final class DetailingCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly CarDetailing $detailing,
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('DetailingCompleted event dispatched', [
            'correlation_id' => $this->correlationId,
            'detailing_id' => $this->detailing->id,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->detailing->tenant_id),
            new PrivateChannel('user.' . $this->detailing->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'detailing.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'detailing_id' => $this->detailing->id,
            'service_type' => $this->detailing->service_type,
            'completed_at' => $this->detailing->completed_at?->toIso8601String(),
            'price' => $this->detailing->price,
            'correlation_id' => $this->correlationId,
        ];
    }
}
