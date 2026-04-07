<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;


use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: достигнут минимальный порог складского запаса.
 */
final class LowStockReached
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $productId,
        public readonly string $productName,
        public readonly int    $tenantId,
        public readonly int    $currentStock,
        public readonly int    $minThreshold,
        public string $correlationId = '',
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('beauty.inventory.' . $this->tenantId),
        ];
    }

    /**
     * Handle broadcastAs operation.
     *
     * @throws \DomainException
     */
    public function broadcastAs(): string
    {
        return 'beauty.stock.low';
    }

    /**
     * Correlation ID для сквозной трассировки.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Представление события для audit-лога.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id'     => $this->productId,
            'product_name'   => $this->productName,
            'tenant_id'      => $this->tenantId,
            'current_stock'  => $this->currentStock,
            'min_threshold'  => $this->minThreshold,
            'correlation_id' => $this->correlationId,
            'event'          => 'beauty.stock.low',
            'timestamp'      => Carbon::now()->toIso8601String(),
        ];
    }
}

