<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;


use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие: расходник закончился.
 * Триггерит уведомление владельцу салона о необходимости закупки.
 */
final class ConsumablesDepleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $consumableId,
        public readonly string $consumableName,
        public readonly int    $tenantId,
        public readonly int    $quantity,
        public readonly string $correlationId,
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
        return 'beauty.consumable.depleted';
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
            'consumable_id'   => $this->consumableId,
            'consumable_name' => $this->consumableName,
            'tenant_id'       => $this->tenantId,
            'quantity'        => $this->quantity,
            'correlation_id'  => $this->correlationId,
            'event'           => 'beauty.consumable.depleted',
            'timestamp'       => Carbon::now()->toIso8601String(),
        ];
    }
}

