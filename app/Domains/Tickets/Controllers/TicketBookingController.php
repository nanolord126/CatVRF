declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Tickets\Requests\PurchaseTicketRequest;
use App\Domains\Tickets\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Канон 2026: Контроллер продажи билетов с Multi-tenancy
 */
final class TicketBookingController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

    /**
     * Покупка билета (Раздел 1: Туризм + Эвенты)
     */
    public function purchase(PurchaseTicketRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $tickets = $this->ticketService->purchaseTickets(
                eventId: $request->integer('event_id'),
                userId: auth()->id(),
                quantity: $request->integer('quantity'),
            );

            return response()->json([
                'success' => true,
                'data' => $tickets,
                'correlation_id' => $correlationId
            ]);

        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Ticket purchase failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при покупке билетов. ' . $e->getMessage(),
                'correlation_id' => $correlationId
            ], 422);
        }
    }
}
