<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Events;

use App\Domains\Supermarket\Models\Return as ReturnModel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class ReturnCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ReturnModel $return,
        public string $correlationId
    ) {}
}
