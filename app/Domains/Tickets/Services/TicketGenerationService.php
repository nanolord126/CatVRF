<?php declare(strict_types=1);

namespace App\Domains\Tickets\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Tickets\Models\{Ticket, TicketType, Event};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Throwable;

final class TicketGenerationService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function generateTickets(
        int $eventId,
        int $ticketTypeId,
        int $quantity,
        int $buyerId,
        string $correlationId = '',
    ): array {


        try {
            Log::channel('audit')->info('Generating tickets', [
                'event_id' => $eventId,
                'ticket_type_id' => $ticketTypeId,
                'quantity' => $quantity,
                'buyer_id' => $buyerId,
                'correlation_id' => $correlationId,
            ]);

            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId ?? \Illuminate\Support\Str::uuid()->toString()
            );

            $tickets = DB::transaction(function () use ($eventId, $ticketTypeId, $quantity, $buyerId, $correlationId) {
                $ticketType = TicketType::findOrFail($ticketTypeId);
                $generatedTickets = [];

                for ($i = 0; $i < $quantity; $i++) {
                    $ticketNumber = 'TKT-' . now()->format('Y') . '-' . Str::random(10);
                    $qrCode = Str::uuid()->toString();

                    $ticket = Ticket::create([
                        'tenant_id' => tenant('id'),
                        'event_id' => $eventId,
                        'ticket_type_id' => $ticketTypeId,
                        'ticket_number' => $ticketNumber,
                        'status' => 'sold',
                        'buyer_id' => $buyerId,
                        'sold_at' => now(),
                        'qr_code' => $qrCode,
                        'checkin_expires_at' => TicketType::find($ticketTypeId)->event->ends_at,
                        'correlation_id' => $correlationId,
                    ]);

                    $generatedTickets[] = $ticket;
                }

                return $generatedTickets;
            });

            Log::channel('audit')->info('Tickets generated', [
                'count' => count($tickets),
                'correlation_id' => $correlationId,
            ]);

            return $tickets;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to generate tickets', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    public function checkinTicket(string $qrCode, string $correlationId = ''): Ticket
    {


        try {
            Log::channel('audit')->info('Checking in ticket', [
                'qr_code' => $qrCode,
                'correlation_id' => $correlationId,
            ]);

            $ticket = Ticket::where('qr_code', $qrCode)
                ->where('status', '!=', 'scanned')
                ->firstOrFail();

            $ticket->update([
                'status' => 'scanned',
                'scanned_at' => now(),
            ]);

            \App\Domains\Tickets\Models\EventCheckin::create([
                'tenant_id' => tenant('id'),
                'event_id' => $ticket->event_id,
                'ticket_id' => $ticket->id,
                'buyer_id' => $ticket->buyer_id,
                'checked_in_at' => now(),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Ticket checked in', [
                'ticket_id' => $ticket->id,
                'correlation_id' => $correlationId,
            ]);

            return $ticket;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to checkin ticket', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
