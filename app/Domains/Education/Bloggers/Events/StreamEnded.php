<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;


final class StreamEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
        
        public function __construct(public readonly Stream $stream) {}

        public function broadcastOn(): Channel
        {
            return new Channel('stream.' . $this->stream->room_id);
        }

        public function broadcastAs(): string
        {
            return 'StreamEnded';
        }

        public function broadcastWith(): array
        {
            return [
                'stream_id' => $this->stream->id,
                'duration_seconds' => $this->stream->duration_seconds,
                'peak_viewers' => $this->stream->peak_viewers,
            ];
        }
    }
