<?php

declare(strict_types=1);

namespace App\Domains\Bloggers\Events;

use App\Domains\Bloggers\Models\Stream;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StreamCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Stream $stream) {}

    public function broadcastOn(): Channel
    {
        return new Channel('admin.bloggers');
    }
}

class StreamStarted implements ShouldBroadcastNow
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

class StreamEnded implements ShouldBroadcastNow
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

class ProductAddedToStream implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly \App\Domains\Bloggers\Models\StreamProduct $product) {}

    public function broadcastOn(): Channel
    {
        return new Channel('stream.' . $this->product->stream->room_id);
    }

    public function broadcastAs(): string
    {
        return 'ProductAdded';
    }
}

class ProductPinned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly \App\Domains\Bloggers\Models\StreamProduct $product) {}

    public function broadcastOn(): Channel
    {
        return new Channel('stream.' . $this->product->stream->room_id);
    }

    public function broadcastAs(): string
    {
        return 'ProductPinned';
    }
}

class ProductUnpinned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly \App\Domains\Bloggers\Models\StreamProduct $product) {}

    public function broadcastOn(): Channel
    {
        return new Channel('stream.' . $this->product->stream->room_id);
    }

    public function broadcastAs(): string
    {
        return 'ProductUnpinned';
    }
}
