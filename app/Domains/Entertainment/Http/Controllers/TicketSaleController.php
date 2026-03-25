<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Http\Controllers;

use App\Domains\Entertainment\Models\TicketSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TicketSaleController
{
    public function myTickets(): JsonResponse
    {
        try {
            $tickets = TicketSale::whereHas('booking', function ($query) {
                $query->where('customer_id', auth()->id());
            })
                ->with('booking')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $tickets, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $ticket = TicketSale::findOrFail($id);

            return response()->json(['success' => true, 'data' => $ticket->load('booking'), 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ticket not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function validateTicket(int $id): JsonResponse
    {
        try {
            $ticket = TicketSale::findOrFail($id);

            $ticket->update(['status' => 'used', 'used_at' => now()]);

            $this->log->channel('audit')->info('Ticket validated', ['ticket_id' => $id]);

            return response()->json(['success' => true, 'data' => $ticket, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function getEventTickets(int $eventId): JsonResponse
    {
        try {
            $tickets = TicketSale::whereHas('booking.eventSchedule', function ($query) use ($eventId) {
                $query->where('entertainment_event_id', $eventId);
            })
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $tickets, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
