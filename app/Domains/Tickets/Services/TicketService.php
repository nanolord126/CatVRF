<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Support\Facades\DB;

use App\Domains\Tickets\Models\Ticket;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class TicketService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function buyTicket(int $eventId, int $quantity): array
    {
        $tickets = [];
        for ($i = 0; $i < $quantity; $i++) {
            $tickets[] = Ticket::create([
                'tenant_id' => auth()->user()->tenant_id,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'event_id' => $eventId,
                'user_id' => auth()->id(),
                'ticket_number' => Str::random(10),
                'status' => 'active',
                'qr_code' => Str::uuid(),
            ]);
        }

        Log::channel('audit')->info('Tickets purchased', [
            'correlation_id' => $this->correlationId,
            'quantity' => $quantity,
        ]);

        return $tickets;
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}