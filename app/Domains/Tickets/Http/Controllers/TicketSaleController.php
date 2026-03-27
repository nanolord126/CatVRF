<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Models\{TicketSale, Event};
use App\Domains\Tickets\Services\TicketSalesService;
use App\Domains\Tickets\Jobs\TicketGenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

final class TicketSaleController
{
    public function __construct(private TicketSalesService $salesService) {}

    public function purchase(int $eventId): JsonResponse
    {
        try {
            $validated = request()->validate([
                'ticket_type_id' => 'required|integer|exists:ticket_types,id',
                'quantity' => 'required|integer|min:1|max:100',
            ]);

            $correlationId = Str::uuid()->toString();

            $sale = DB::transaction(function () use ($eventId, $validated, $correlationId) {
                return $this->salesService->createSale(
                    $eventId,
                    $validated['ticket_type_id'],
                    $validated['quantity'],
                    auth()->id(),
                    $correlationId
                );
            });

            TicketGenerationJob::dispatch($sale->id, $correlationId);

            \Log::channel('audit')->info('Ticket purchase initiated', [
                'event_id' => $eventId,
                'quantity' => $validated['quantity'],
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $sale,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Ticket purchase failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $sales,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list sales', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $sale,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to show sale', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            $reason = request()->input('reason', 'User requested refund');
            $correlationId = Str::uuid()->toString();

            DB::transaction(function () use ($sale, $reason, $correlationId) {
                $this->salesService->refundSale($sale, $reason, $correlationId);
            });

            \Log::channel('audit')->info('Ticket sale refunded', [
                'sale_id' => $id,
                'reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Refund processed',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Refund failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Refund failed',
            ], 500);
        }
    }
}
