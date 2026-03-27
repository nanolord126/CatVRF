<?php

declare(strict_types=1);


namespace App\Domains\Auto\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final /**
 * AutoPartOrderCreated
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AutoPartOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $autoPartOrderId,
        public readonly int $tenantId,
        public readonly int $userId,
        public readonly int $totalPrice,
        public readonly string $correlationId,
    ) {
        Log::channel('audit')->info('AutoPartOrderCreated event dispatched', [
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
