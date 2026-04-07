<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;

use Dispatchable, InteractsWithSockets, SerializesModels;

final class ProductAddedToStream implements ShouldBroadcastNow
{
        use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(public readonly \App\Domains\Content\Bloggers\Models\StreamProduct $product) {}

        public function broadcastOn(): Channel
        {
            return new Channel('stream.' . $this->product->stream->room_id);
        }

        public function broadcastAs(): string
        {
            return 'ProductAdded';
        }
    }
