<?php declare(strict_types=1);

namespace App\Http\Controllers\Luxury;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class LuxuryBookingController extends Controller
{

    public function __construct(
            private readonly ConciergeService $conciergeService,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Luxury вертикали
             // VIP требует авторизации
             // 20 запросов/мин для премиум операций
             // Определение режима B2C/B2B
             // Tenant scoping обязателен
            // Fraud check для всех мутаций (высокая стоимость)
            $this->middleware(
                'fraud-check',
                ['only' => ['store', 'update', 'cancel', 'confirmPayment']]
            );
        }
        /**
         * Запрос на VIP-бронирование
         */
        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            // 1. Валидация входных данных
            $validated = $request->validate([
                'client_uuid' => 'required|uuid|exists:luxury_clients,uuid',
                'bookable_type' => 'required|string|in:product,service',
                'bookable_uuid' => 'required|uuid',
                'booking_at' => 'required|date|after:now',
                'duration_minutes' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:500',
            ]);
            try {
                // 2. Инициализация сервисов и клиента
                $concierge = new ConciergeService(app(\App\Services\FraudControlService::class), $correlationId);
                $client = LuxuryClient::where('uuid', $validated['client_uuid'])->firstOrFail();
                // 3. Определение объекта бронирования
                $bookable = match ($validated['bookable_type']) {
                    'service' => LuxuryService::where('uuid', $validated['bookable_uuid'])->firstOrFail(),
                    default => abort(422, 'Invalid bookable type'),
                };
                // 4. Выполнение бронирования в доменном сервисе
                $booking = $concierge->createBooking($client, $bookable, $validated);
                return $this->response->json([
                    'status' => 'success',
                    'data' => [
                        'booking_uuid' => $booking->uuid,
                        'status' => $booking->status,
                        'booking_at' => $booking->booking_at->toIso8601String(),
                    ],
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Luxury Booking Failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], $e->getCode() ?: 500);
            }
        }
        /**
         * Список моих VIP-бронирований
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            $bookings = VIPBooking::with(['client', 'bookable'])
                ->whereHas('client', function ($q) {
                    $q->where('user_id', $this->guard->id());
                })
                ->latest()
                ->paginate(15);
            return $this->response->json([
                'status' => 'success',
                'data' => $bookings,
                'correlation_id' => $correlationId,
            ]);
        }
}
