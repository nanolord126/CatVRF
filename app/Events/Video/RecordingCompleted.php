<?php

declare(strict_types=1);

namespace App\Events\Video;

use App\Models\Video\VideoRecording;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RecordingCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly VideoRecording $recording
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-room.' . $this->recording->video_room_id),
            new Channel('tenant.' . $this->recording->tenant_id . '.video'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'recording_id' => $this->recording->id,
            'video_room_id' => $this->recording->video_room_id,
            'title' => $this->recording->title,
            'processing_status' => $this->recording->processing_status,
            'duration_seconds' => $this->recording->duration_seconds,
            'size_bytes' => $this->recording->size_bytes,
            'playback_url' => $this->recording->getPlaybackUrl(),
            'thumbnail_url' => $this->recording->thumbnail_url,
        ];
    }
}
