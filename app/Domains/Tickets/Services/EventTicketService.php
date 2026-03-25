<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use Illuminate\Support\Facades\DB;

final class EventTicketService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,)
    {
    }

    /**
     * Продать билет
     */
    public function purchaseTicket(int $eventId, int $ticketTypeId, string $correlationId): bool
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $this->db->transaction(function () use ($eventId, $ticketTypeId, $correlationId) {
                $this->db->table('ticket_orders')->insert([
                    'event_id' => $eventId,
                    'ticket_type_id' => $ticketTypeId,
                    'status' => 'sold',
                    'correlation_id' => $correlationId,
                    'created_at' => now(),
                ]);

                $this->log->channel('audit')->info('Ticket purchased', [
                    'event_id' => $eventId,
                    'ticket_type_id' => $ticketTypeId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ticket purchase failed', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Отменить билет (возврат денег)
     */
    public function refundTicket(int $ticketOrderId, string $correlationId): bool
    {


        try {
                        $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );
            $this->db->transaction(function () use ($ticketOrderId, $correlationId) {
                $this->db->table('ticket_orders')
                    ->where('id', $ticketOrderId)
                    ->update(['status' => 'refunded', 'refunded_at' => now()]);

                $this->log->channel('audit')->info('Ticket refunded', [
                    'ticket_order_id' => $ticketOrderId,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Ticket refund failed', [
                'ticket_order_id' => $ticketOrderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
