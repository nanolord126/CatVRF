<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;

use Dispatchable, InteractsWithSockets, SerializesModels;

final class StreamCreated
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(public readonly Stream $stream) {}

        public function broadcastOn(): Channel
        {
            return new Channel('admin.bloggers');
        }
    }
