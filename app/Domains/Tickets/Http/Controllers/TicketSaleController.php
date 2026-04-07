<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Domains\Tickets\Jobs\TicketGenerationJob;
use App\Domains\Tickets\Models\Event;
use App\Domains\Tickets\Models\TicketSale;
use App\Domains\Tickets\Services\TicketSalesService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class TicketSaleController
{
    public function __construct(private TicketSalesService $salesService,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function purchase(int $eventId): JsonResponse
        {
            try {
                $validated = $request->validate([
                    'ticket_type_id' => 'required|integer|exists:ticket_types,id',
                    'quantity' => 'required|integer|min:1|max:100',
                ]);

                $correlationId = Str::uuid()->toString();

                $sale = $this->db->transaction(function () use ($eventId, $validated, $correlationId) {
                    return $this->salesService->createSale(
                        $eventId,
                        $validated['ticket_type_id'],
                        $validated['quantity'],
                        $request->user()?->id,
                        $correlationId
                    );
                });

                TicketGenerationJob::dispatch($sale->id, $correlationId);

                $this->logger->info('Ticket purchase initiated', [
                    'event_id' => $eventId,
                    'quantity' => $validated['quantity'],
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $sale,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Ticket purchase failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Purchase failed',
                ], 500);
            }
        }

        public function eventSales(int $eventId): JsonResponse
        {
            try {
                $event = Event::findOrFail($eventId);
                $this->authorize('update', $event);

                $sales = TicketSale::where('event_id', $eventId)
                    ->with(['buyer', 'event'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $sales,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list sales', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list sales',
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $sale = TicketSale::findOrFail($id);
                $this->authorize('view', $sale);

                $sale->load(['event', 'buyer', 'organizer']);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $sale,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to show sale', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Sale not found',
                ], 404);
            }
        }

        public function refund(int $id): JsonResponse
        {
            try {
                $sale = TicketSale::findOrFail($id);
                $this->authorize('refund', $sale);

                $reason = $request->input('reason', 'User requested refund');
                $correlationId = Str::uuid()->toString();

                $this->db->transaction(function () use ($sale, $reason, $correlationId) {
                    $this->salesService->refundSale($sale, $reason, $correlationId);
                });

                $this->logger->info('Ticket sale refunded', [
                    'sale_id' => $id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Refund processed',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Refund failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Refund failed',
                ], 500);
            }
        }
}
