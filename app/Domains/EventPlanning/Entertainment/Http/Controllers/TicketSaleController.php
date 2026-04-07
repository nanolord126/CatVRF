<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TicketSaleController extends Controller
{
    public function __construct(
        private readonly LoggerInterface $logger) {}


    public function myTickets(): JsonResponse
        {
            try {
                $tickets = TicketSale::whereHas('booking', function ($query) {
                    $query->where('customer_id', $request->user()?->id);
                })
                    ->with('booking')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $tickets, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $ticket = TicketSale::findOrFail($id);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $ticket->load('booking'), 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ticket not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function validateTicket(int $id): JsonResponse
        {
            try {
                $ticket = TicketSale::findOrFail($id);

                $ticket->update(['status' => 'used', 'used_at' => Carbon::now()]);

                $this->logger->info('Ticket validated', ['ticket_id' => $id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $ticket, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function getEventTickets(int $eventId): JsonResponse
        {
            try {
                $tickets = TicketSale::whereHas('booking.eventSchedule', function ($query) use ($eventId) {
                    $query->where('entertainment_event_id', $eventId);
                })
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $tickets, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
