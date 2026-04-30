<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;

use App\Domains\Education\Bloggers\Models\Stream;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StreamCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Stream $stream,
        public readonly string $correlationId = '',
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('admin.bloggers');
    }
}
