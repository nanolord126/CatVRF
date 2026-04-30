<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use App\Domains\Content\Bloggers\Models\StreamProduct;

final class ProductUnpinned implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly StreamProduct $product) {}

    public function broadcastOn(): Channel
    {
        return new Channel('stream.'.$this->product->stream->room_id);
    }

    public function broadcastAs(): string
    {
        return 'ProductUnpinned';
    }
}
