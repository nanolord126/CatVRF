<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Ticket, TicketSale};
    use App\Domains\Tickets\Services\TicketGenerationService;
    use Illuminate\Http\JsonResponse;
    use Illuminate\Support\Str;
    use Barryvdh\DomPDF\Facade\Pdf;

    final class TicketController
    {
        public function __construct(private TicketGenerationService $generationService) {}

        public function myTickets(): JsonResponse
        {
            try {
                $tickets = Ticket::where('buyer_id', auth()->id())
                    ->with(['event', 'ticketType', 'checkin'])
                    ->paginate(20);

                return response()->json([
                    'success' => true,
                    'data' => $tickets,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                \Log::channel('audit')->error('Failed to list my tickets', [
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to list tickets',
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $ticket = Ticket::findOrFail($id);
                $this->authorize('view', $ticket);

                $ticket->load(['event', 'ticketType', 'buyer', 'checkin']);

                return response()->json([
                    'success' => true,
                    'data' => $ticket,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                \Log::channel('audit')->error('Failed to show ticket', [
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                ], 404);
            }
        }

        public function download(int $id)
        {
            try {
                $ticket = Ticket::findOrFail($id);
                $this->authorize('view', $ticket);

                $ticket->load(['event', 'ticketType']);

                $pdf = Pdf::loadView('tickets.pdf', [
                    'ticket' => $ticket,
                    'qrCode' => \QrCode::format('svg')->size(300)->generate($ticket->qr_code),
                ]);

                return $pdf->download($ticket->ticket_number . '.pdf');
            } catch (\Throwable $e) {
                \Log::channel('audit')->error('Failed to download ticket', [
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to download ticket',
                ], 500);
            }
        }

        public function checkin(int $eventId): JsonResponse
        {
            try {
                $validated = request()->validate([
                    'qr_code' => 'required|string',
                ]);

                $correlationId = Str::uuid()->toString();

                $this->generationService->checkinTicket($validated['qr_code'], $correlationId);

                \Log::channel('audit')->info('Ticket checked in', [
                    'event_id' => $eventId,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Ticket checked in successfully',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                \Log::channel('audit')->error('Checkin failed', [
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Checkin failed',
                ], 400);
            }
        }
}
