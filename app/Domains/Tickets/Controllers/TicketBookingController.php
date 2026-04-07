<?php declare(strict_types=1);

/**
 * TicketBookingController — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ticketbookingcontroller
 */


namespace App\Domains\Tickets\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TicketBookingController extends Controller
{

    public function __construct(
            private readonly TicketService $ticketService, private readonly LoggerInterface $logger
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
                    userId: $request->user()?->id,
                    quantity: $request->integer('quantity'),
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $tickets,
                    'correlation_id' => $correlationId
                ]);

            } catch (\Throwable $e) {
                $this->logger->error('Ticket purchase failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Ошибка при покупке билетов. ' . $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 422);
            }
        }
}
