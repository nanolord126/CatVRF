<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;


use Psr\Log\LoggerInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
// Предполагается, что модель ServiceWarranty будет перемещена или уже существует
// use App\Domains\Auto\Shared\Infrastructure\Persistence\Eloquent\Models\ServiceWarranty;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

final class ServiceWarrantyClaimApproved
{
    
    /**
     * Create a new event instance.
     *
     * @param mixed $warranty
     * @param string $correlationId
     */
    public function __construct(
        public readonly mixed $warranty, // Implemented per canon 2026
        public readonly string $correlationId, public readonly LoggerInterface $logger
    ) {
        $this->logger->info('ServiceWarrantyClaimApproved event dispatched', [
            'correlation_id' => $this->correlationId,
            // 'warranty_id' => $this->warranty->id, // Раскомментировать после определения модели
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // return [
        //     new PrivateChannel('tenant.' . $this->warranty->tenant_id),
        //     new PrivateChannel('user.' . $this->warranty->client_id),
        // ];
        return []; // Implemented per canon 2026
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'warranty.service.claim.approved';
    }
}

