<?php

declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use App\Domains\Auto\Models\AutoPart;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
final class LowPartsStock implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AutoPart $part,
        public readonly string $correlationId, public readonly LoggerInterface $logger) {
        $this->logger->warning('Low parts stock detected', [
            'correlation_id' => $this->correlationId,
            'part_id' => $this->part->id,
            'part_name' => $this->part->name,
            'current_stock' => $this->part->current_stock,
            'min_threshold' => $this->part->min_stock_threshold,
            'tenant_id' => $this->part->tenant_id,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->part->tenant_id}.auto.inventory"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'auto.parts.low.stock';
    }

    public function broadcastWith(): array
    {
        return [
            'part_id' => $this->part->id,
            'part_name' => $this->part->name,
            'current_stock' => $this->part->current_stock,
            'min_threshold' => $this->part->min_stock_threshold,
            'correlation_id' => $this->correlationId,
        ];
    }

    public function shouldBroadcast(): bool
    {
        return $this->part->current_stock < $this->part->min_stock_threshold;
    }
}
