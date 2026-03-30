<?php declare(strict_types=1);

namespace App\Domains\Tickets\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TicketBookingController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly TicketService $ticketService
        ) {}

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
                Log::channel('audit')->error('Ticket purchase failed', [
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
