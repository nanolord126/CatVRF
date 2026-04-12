<?php

declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;


final class StreamCreated
{

    
        public function __construct(public readonly Stream $stream) {}

        public function broadcastOn(): Channel
        {
            return new Channel('admin.bloggers');
        }
    }
