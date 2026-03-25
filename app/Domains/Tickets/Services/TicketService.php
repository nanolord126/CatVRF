<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

use App\Domains\Tickets\Models\Ticket;
use Illuminate\Support\Str;

final class TicketService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function buyTicket(int $eventId, int $quantity, int $userId, int $tenantId): array
    {
        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($eventId, $quantity, $userId, $tenantId) {
        $tickets = [];
        for ($i = 0; $i < $quantity; $i++) {
            $tickets[] = Ticket::create([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $this->correlationId,
                'event_id' => $eventId,
                'user_id' => $userId,
                'ticket_number' => Str::random(10),
                'status' => 'active',
                'qr_code' => Str::uuid(),
            ]);
        }

        $this->log->channel('audit')->info('Tickets purchased', [
            'correlation_id' => $this->correlationId,
            'quantity' => $quantity,
        ]);

        return $tickets;
        });
    }

    /**
     * Выполняет операцию в транзакции с аудитом.
     */
    public function executeInTransaction(callable $callback)
    {


        $this->fraudControlService->check(
            auth()->id() ?? 0,
            __CLASS__ . '::' . __FUNCTION__,
            0,
            request()->ip(),
            null,
            $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
        );
$this->db->transaction(function () use ($callback) {
            return $callback();
        });
    }
}