<?php declare(strict_types=1);

namespace App\Domains\Realtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RealtimeMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $channel,
        public readonly string $event,
        public readonly array $data,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel($this->channel);
    }

    public function broadcastAs(): string
    {
        return $this->event;
    }
}
