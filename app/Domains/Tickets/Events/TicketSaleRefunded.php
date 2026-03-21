<?php declare(strict_types=1);

namespace App\Domains\Tickets\Events;

use App\Domains\Tickets\Models\TicketSale;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketSaleRefunded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketSale $ticketSale,
        public string $reason = '',
        public string $correlationId = '',
    ) {}
}
