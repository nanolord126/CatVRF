<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\ShortTermRentals;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class PropertyController extends Controller
{

    public function __construct(
            private readonly PropertyService $propertyService,
            private readonly FraudControlService $fraudService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {}

        /**
         * Получить список доступных квартир с фильтрацией
         */
        public function index(Request $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                // Базовая фильтрация
                $query = Property::where('is_active', true);

                // Гео-фильтрация если передали координаты
                if ($request->has(['lat', 'lon'])) {
                    $lat = (float)$request->get('lat');
                    $lon = (float)$request->get('lon');
                    $radius = (float)$request->get('radius_km', 5);

                    $query = $query->selectRaw(
                        '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                        [$lat, $lon, $lat]
                    )->having('distance', '<=', $radius)
                        ->orderBy('distance');
                }

                // Фильтры по цене
                if ($request->has('price_min') || $request->has('price_max')) {
                    $minPrice = $request->get('price_min') ? (int)($request->get('price_min') * 100) : 0;
                    $maxPrice = $request->get('price_max') ? (int)($request->get('price_max') * 100) : 999999999;
                    $query = $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
                }

                // Фильтры по датам доступности
                if ($request->has(['check_in', 'check_out'])) {
                    $checkIn = $request->get('check_in');
                    $checkOut = $request->get('check_out');

                    // Исключаем забронированные периоды
                    $query = $query->whereDoesntHave('bookings', function ($q) use ($checkIn, $checkOut) {
                        $q->where('status', '!=', 'cancelled')
                            ->where(function ($q) use ($checkIn, $checkOut) {
                                $q->whereBetween('check_in_date', [$checkIn, $checkOut])
                                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                                    ->orWhere([
                                        ['check_in_date', '<=', $checkIn],
                                        ['check_out_date', '>=', $checkOut],
                                    ]);
                            });
                    });
                }

                // B2C vs B2B фильтрация
                if ($request->get('b2b') === true) {
                    $query = $query->where('is_b2b_available', true);
                } else {
                    $query = $query->where('is_b2c_available', true);
                }

                // Фильтры по удобствам
                if ($request->has('amenities')) {
                    $amenities = (array)$request->get('amenities');
                    foreach ($amenities as $amenity) {
                        $query = $query->whereJsonContains('amenities', $amenity);
                    }
                }

                $properties = $query->paginate(20);

                $this->logger->channel('audit')->info('Properties list requested', [
                    'count' => count($properties),
                    'filters' => $request->only(['lat', 'lon', 'price_min', 'price_max', 'check_in', 'check_out']),
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => PropertyResource::collection($properties),
                    'meta' => [
                        'pagination' => [
                            'total' => $properties->total(),
                            'count' => count($properties),
                            'per_page' => $properties->perPage(),
                            'current_page' => $properties->currentPage(),
                        ],
                    ],
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Properties list failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to fetch properties',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 500);
            }
        }

        /**
         * Получить детали квартиры с расписанием
         */
        public function show(Property $property, Request $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                // Загружаем отношения
                $property->load([
                    'owner',
                    'bookings' => function ($q) {
                        $q->where('status', '!=', 'cancelled')
                            ->where('check_out_date', '>=', now()->toDateString())
                            ->orderBy('check_in_date');
                    },
                    'reviews' => function ($q) {
                        $q->where('is_approved', true)->orderBy('created_at', 'desc')->limit(10);
                    },
                ]);

                // Проверяем доступность для B2C/B2B
                $isB2B = $request->get('b2b') === true;
                if ($isB2B && !$property->is_b2b_available) {
                    return $this->response->json([
                        'success' => false,
                        'error' => 'Property not available for B2B',
                        'correlation_id' => $correlationId,
                    ], 403);
                }
                if (!$isB2B && !$property->is_b2c_available) {
                    return $this->response->json([
                        'success' => false,
                        'error' => 'Property not available for B2C',
                        'correlation_id' => $correlationId,
                    ], 403);
                }

                $this->logger->channel('audit')->info('Property details viewed', [
                    'property_id' => $property->id,
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => new PropertyResource($property),
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Property details failed', [
                    'property_id' => $property->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to fetch property',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 500);
            }
        }

        /**
         * Обновить информацию о квартире (только для владельца)
         */
        public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                // Проверяем авторизацию
                $this->authorize('update', $property);

                $this->db->transaction(function () use ($request, $property, $correlationId) {
                    $this->propertyService->updateProperty($property, $request->validated(), $correlationId);
                });

                $this->logger->channel('audit')->info('Property updated', [
                    'property_id' => $property->id,
                    'user_id' => $this->guard->id(),
                    'fields' => array_keys($request->validated()),
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => new PropertyResource($property),
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Property update failed', [
                    'property_id' => $property->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to update property',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 500);
            }
        }
    }

    final class BookingController extends Controller
    {
        public function __construct(
            private readonly BookingService $bookingService,
            private readonly FraudControlService $fraudService,
        ) {}

        /**
         * Создать новое бронирование (с холдом депозита)
         */
        public function store(CreateBookingRequest $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                $this->db->transaction(function () use ($request, $correlationId) {
                    // Фрод-проверка перед бронированием
                    $fraudScore = $this->fraudService->scoreOperation(
                        operationType: 'property_booking',
                        userId: $this->guard->id(),
                        amount: (int)($request->total_price * 100),
                        correlationId: $correlationId
                    );

                    if ($fraudScore > 0.85) {
                        throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Booking blocked by fraud detection');
                    }

                    // Создаём бронирование
                    $booking = $this->bookingService->createBooking(
                        propertyId: $request->property_id,
                        userId: $this->guard->id(),
                        checkIn: $request->check_in_date,
                        checkOut: $request->check_out_date,
                        guests: $request->guests_count,
                        totalPrice: $request->total_price,
                        correlationId: $correlationId
                    );
                });

                $this->logger->channel('audit')->info('Booking created', [
                    'booking_id' => $booking->id ?? null,
                    'property_id' => $request->property_id,
                    'user_id' => $this->guard->id(),
                    'total_price' => $request->total_price,
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => new BookingResource($booking),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Booking creation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to create booking',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 400);
            }
        }

        /**
         * Получить детали бронирования
         */
        public function show(PropertyBooking $booking, Request $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                $this->authorize('view', $booking);

                $this->logger->channel('audit')->info('Booking details viewed', [
                    'booking_id' => $booking->id,
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => new BookingResource($booking),
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Booking details failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to fetch booking',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 500);
            }
        }

        /**
         * Отменить бронирование (с возвратом депозита)
         */
        public function cancel(PropertyBooking $booking, Request $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                $this->authorize('cancel', $booking);

                $this->db->transaction(function () use ($booking, $correlationId, $request) {
                    $this->bookingService->cancelBooking(
                        booking: $booking,
                        reason: $request->get('reason', 'user_requested'),
                        correlationId: $correlationId
                    );
                });

                $this->logger->channel('audit')->info('Booking cancelled', [
                    'booking_id' => $booking->id,
                    'user_id' => $this->guard->id(),
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => new BookingResource($booking),
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Booking cancellation failed', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to cancel booking',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 400);
            }
        }
    }

    final class PayoutController extends Controller
    {
        public function __construct(
            private readonly PayoutService $payoutService,
        ) {}

        /**
         * Получить историю выплат для владельца
         */
        public function index(Request $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                $payouts = $this->payoutService->getPayoutHistory(
                    tenantId: $this->guard->user()->current_tenant_id,
                    page: $request->get('page', 1),
                    perPage: $request->get('per_page', 20)
                );

                $this->logger->channel('audit')->info('Payouts list requested', [
                    'tenant_id' => $this->guard->user()->current_tenant_id,
                    'count' => count($payouts),
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'data' => $payouts,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Payouts list failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to fetch payouts',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 500);
            }
        }

        /**
         * Запросить выплату (для B2B)
         */
        public function requestPayout(Request $request): JsonResponse
        {
            try {
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();

                $request->validate([
                    'amount' => 'required|numeric|min:500',
                    'bank_account' => 'required|string',
                ]);

                $this->db->transaction(function () use ($request, $correlationId) {
                    $this->payoutService->requestPayout(
                        tenantId: $this->guard->user()->current_tenant_id,
                        amount: (int)($request->amount * 100),
                        bankAccount: $request->bank_account,
                        correlationId: $correlationId
                    );
                });

                $this->logger->channel('audit')->info('Payout requested', [
                    'tenant_id' => $this->guard->user()->current_tenant_id,
                    'amount' => $request->amount,
                    'correlation_id' => $correlationId,
                ]);

                return $this->response->json([
                    'success' => true,
                    'message' => 'Payout request created',
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('Payout request failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to request payout',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 400);
            }
        }
}
