<?php

declare(strict_types=1);

namespace App\Events\Video;

use App\Models\Video\VideoParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ParticipantJoined implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly VideoParticipant $participant
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-room.' . $this->participant->video_room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participant->id,
            'video_room_id' => $this->participant->video_room_id,
            'user_id' => $this->participant->user_id,
            'user_type' => $this->participant->user_type,
            'role' => $this->participant->role,
            'status' => $this->participant->status,
            'joined_at' => $this->participant->joined_at?->toIso8601String(),
        ];
    }
}
