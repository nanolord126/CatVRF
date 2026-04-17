<?php declare(strict_types=1);

namespace App\Domains\Fashion\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class WebRTCSessionInitiatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionId,
        public int $userId,
        public int $stylistId,
        public int $tenantId,
        public ?int $businessGroupId,
        public Carbon $scheduledAt,
        public string $correlationId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('fashion.' . $this->userId),
            new PrivateChannel('stylist.' . $this->stylistId),
            new PrivateChannel('tenant.' . $this->tenantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'fashion.webrtc.session.initiated';
    }
}
