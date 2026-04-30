<?php

declare(strict_types=1);

namespace App\Events\Video;

use App\Models\Video\VideoRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class VideoRoomStarted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly VideoRoom $room,
        public readonly string $message = 'Video call has started'
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-room.' . $this->room->id),
            new Channel('tenant.' . $this->room->tenant_id . '.video'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->room->id,
            'room_name' => $this->room->room_name,
            'type' => $this->room->type,
            'status' => $this->room->status,
            'started_at' => $this->room->started_at->toIso8601String(),
            'message' => $this->message,
        ];
    }
}
