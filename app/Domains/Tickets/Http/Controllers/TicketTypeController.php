<?php declare(strict_types=1);

namespace App\Domains\Tickets\Http\Controllers;

use App\Domains\Tickets\Models\TicketType;
use App\Domains\Tickets\Models\Event;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class TicketTypeController extends Controller
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}
    public function byEvent(int $eventId): JsonResponse
    {
        try {
            $ticketTypes = TicketType::where('event_id', $eventId)
                ->where('is_active', true)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ticketTypes,
                'correlation_id' => Str::uuid()->toString(),
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to list ticket types', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to list ticket types',
            ], 500);
        }
    }

    public function store(int $eventId): JsonResponse
    {
        $correlationId = (string) Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'ticket_type_store', 0, request()->ip(), null, $correlationId);

        try {
            $event = Event::findOrFail($eventId);
            $this->authorize('create', TicketType::class);

            $validated = request()->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'total_quantity' => 'required|integer|min:1',
                'sale_starts_at' => 'required|date',
                'sale_ends_at' => 'required|date|after:sale_starts_at',
                'max_per_buyer' => 'nullable|integer|min:1',
                'restrictions' => 'nullable|array',
            ]);

            $ticketType = TicketType::create([
                'tenant_id' => tenant('id'),
                'event_id' => $eventId,
                'name' => $validated['name'],
                'price' => $validated['price'],
                'total_quantity' => $validated['total_quantity'],
                'sold_quantity' => 0,
                'reserved_quantity' => 0,
                'sale_starts_at' => $validated['sale_starts_at'],
                'sale_ends_at' => $validated['sale_ends_at'],
                'max_per_buyer' => $validated['max_per_buyer'] ?? null,
                'is_active' => true,
                'restrictions' => $validated['restrictions'] ?? [],
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Ticket type created', [
                'ticket_type_id' => $ticketType->id,
                'event_id' => $eventId,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $ticketType,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create ticket type', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket type',
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $correlationId = (string) Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'ticket_type_update', 0, request()->ip(), null, $correlationId);

        try {
            $ticketType = TicketType::findOrFail($id);
            $this->authorize('update', $ticketType);

            $validated = request()->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'is_active' => 'sometimes|boolean',
            ]);

            $ticketType->update($validated + ['correlation_id' => $correlationId]);

            Log::channel('audit')->info('Ticket type updated', [
                'ticket_type_id' => $ticketType->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $ticketType,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to update ticket type', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket type',
            ], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $ticketType = TicketType::findOrFail($id);
            $this->authorize('delete', $ticketType);

            $correlationId = (string) Str::uuid()->toString();
            $ticketType->delete();

            Log::channel('audit')->info('Ticket type deleted', [
                'ticket_type_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket type deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to delete ticket type', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete ticket type',
            ], 500);
        }
    }
}
