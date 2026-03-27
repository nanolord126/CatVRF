<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Tickets;
use App\Http\Controllers\Controller;
use App\Domains\Tickets\Services\TicketService;
use App\Domains\Tickets\DTO\BuyTicketDto;
use App\Http\Requests\Api\Tickets\TicketPurchaseRequest;
use App\Http\Requests\Api\Tickets\TicketCheckInRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
/**
 * КАНОН 2026: Финальная версия API контроллера с типизированными реквестами.
 * Слой 8: API.
 */
final class TicketApiController extends Controller
{
    /**
     * Конструктор с зависимостями.
     */
    public function __construct(
        private readonly TicketService $ticketService
    ) {}
    /**
     * Покупка билета через типизированный реквест.
     */
    public function purchase(TicketPurchaseRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        try {
            // 2. Создание DTO из валидированного реквеста
            $dto = BuyTicketDto::fromArray(array_merge($request->validated(), [
                'user_id' => auth()->id() ?? 1,
                'correlation_id' => $correlationId
            ]));
            // 3. Вызов сервиса
            $tickets = $this->ticketService->buyTickets($dto);
            return response()->json([
                'success' => true,
                'correlation_id' => $correlationId,
                'data' => [
                    'tickets' => collect($tickets)->map(fn ($t) => [
                        'uuid' => $t->uuid,
                        'qr_code' => $t->qr_code,
                        'seat' => $t->seat_string,
                        'status' => $t->status,
                        'expires_at' => $t->expires_at->toIso8601String()
                    ]),
                ],
                'message' => 'Билеты успешно куплены и доступны в вашем кабинете'
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Ticket purchase failed (API)', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'success' => false,
                'correlation_id' => $correlationId,
                'error' => $e->getMessage()
            ], 400);
        }
    }
    /**
     * Проверка билета (Check-in) через типизированный реквест.
     */
    public function checkIn(TicketCheckInRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        try {
            $result = $this->ticketService->checkIn(
                qrCode: $request->input('qr_code'),
                checkerUserId: auth()->id() ?? 1,
                requestData: array_merge($request->validated(), ['correlation_id' => $correlationId])
            );
            return response()->json(array_merge($result, [
                'correlation_id' => $correlationId
            ]), $result['success'] ? 200 : 400);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Ticket check-in API fatal error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'success' => false,
                'correlation_id' => $correlationId,
                'error' => 'Внутренняя ошибка при проверке билета'
            ], 500);
        }
    }
    /**
     * Получить билеты пользователя.
     */
    public function getMyTickets(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
        $tickets = \App\Domains\Tickets\Models\Ticket::where('user_id', auth()->id() ?? 1)
            ->with(['event', 'ticketType'])
            ->orderBy('id', 'desc')
            ->paginate(15);
        return response()->json([
            'success' => true,
            'correlation_id' => $correlationId,
            'data' => $tickets
        ]);
    }
}
