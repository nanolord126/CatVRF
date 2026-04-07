<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Tickets\Models\Ticket;
use App\Domains\Tickets\Models\TicketSale;
use App\Domains\Tickets\Services\TicketGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class TicketController
{
    public function __construct(private TicketGenerationService $generationService, private readonly LoggerInterface $logger) {}

        public function myTickets(): JsonResponse
        {
            try {
                $tickets = Ticket::where('buyer_id', $request->user()?->id)
                    ->with(['event', 'ticketType', 'checkin'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $tickets,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list my tickets', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $ticket,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to show ticket', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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
                $this->logger->error('Failed to download ticket', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to download ticket',
                ], 500);
            }
        }

        public function checkin(int $eventId): JsonResponse
        {
            try {
                $validated = $request->validate([
                    'qr_code' => 'required|string',
                ]);

                $correlationId = Str::uuid()->toString();

                $this->generationService->checkinTicket($validated['qr_code'], $correlationId);

                $this->logger->info('Ticket checked in', [
                    'event_id' => $eventId,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Ticket checked in successfully',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Checkin failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Checkin failed',
                ], 400);
            }
        }
}
