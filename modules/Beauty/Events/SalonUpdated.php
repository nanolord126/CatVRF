<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SalonUpdated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public $salon)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('beauty');
    }
}
