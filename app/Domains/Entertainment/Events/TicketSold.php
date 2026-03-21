<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Events;

use App\Domains\Entertainment\Models\TicketSale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketSold
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketSale $ticket,
        public string $correlationId,
    ) {}
}
