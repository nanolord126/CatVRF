<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Events;

use App\Domains\FarmDirect\Models\FarmOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FarmOrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly FarmOrder $order,
        public readonly string    $correlationId,
    ) {}
}
