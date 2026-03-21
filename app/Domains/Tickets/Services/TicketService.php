<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use App\Services\Security\FraudControlService;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

use App\Domains\Tickets\Models\Ticket;
use Illuminate\Support\Str;

final class TicketService
{
    public function __construct(
        private readonly string $correlationId = '',
    ) {
        $this->correlationId = $correlationId ?: Str::uuid()->toString();
    }

    public function buyTicket(int $eventId, int $quantity): array
    {
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'buyTicket'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL buyTicket', ['domain' => __CLASS__]);

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
        // Canon 2026: Mandatory Fraud Check & Audit
        $correlationId = $correlationId ?? (string)\Illuminate\Support\Str::uuid();
        \App\Services\Security\FraudControlService::check(['method' => 'executeInTransaction'], $correlationId ?? 'system');
        \Illuminate\Support\Facades\Log::channel('audit')->info('CALL executeInTransaction', ['domain' => __CLASS__]);

        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
}