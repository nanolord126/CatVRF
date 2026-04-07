<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;

use Dispatchable, InteractsWithSockets, SerializesModels;

final class StreamStarted implements ShouldBroadcastNow
{
        use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(public readonly Stream $stream) {}

        public function broadcastOn(): Channel
        {
            return new Channel('stream.' . $this->stream->room_id);
        }

        public function broadcastAs(): string
        {
            return 'StreamStarted';
        }

        public function broadcastWith(): array
        {
            return [
                'stream_id' => $this->stream->id,
                'room_id' => $this->stream->room_id,
                'title' => $this->stream->title,
                'started_at' => $this->stream->started_at,
            ];
        }
    }
