<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Presentation\Http\Controllers;


use Illuminate\Contracts\Routing\ResponseFactory;
use App\Domains\Hotels\Application\DTO\BookingDTO;
use App\Domains\Hotels\Application\UseCases\B2C\BookRoomUseCase;
use App\Domains\Hotels\Application\UseCases\B2C\SearchHotelsUseCase;
use App\Domains\Hotels\Presentation\Http\Requests\BookRoomRequest;
use App\Domains\Hotels\Presentation\Http\Requests\SearchHotelsRequest;
use App\Domains\Hotels\Presentation\Http\Resources\BookingResource;
use App\Domains\Hotels\Presentation\Http\Resources\HotelResource;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * HotelController — B2C API контроллер для работы с отелями.
 *
 * Использует строгое DI: все зависимости передаются через конструктор.
 * Статические фасады ($this->logger->, Str::) не используются.
 * LoggerInterface привязывается к каналу audit через HotelsServiceProvider.
 * UUID генерируется через Ramsey\Uuid без статического фасада.
 *
 * @package App\Domains\Hotels\Presentation\Http\Controllers
 */
final class HotelController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly SearchHotelsUseCase $searchHotelsUseCase,
        private readonly BookRoomUseCase $bookRoomUseCase,
        private readonly LoggerInterface $auditLogger, private readonly LoggerInterface $logger) {
    }

    /**
     * Поиск отелей по критериям из SearchHotelsRequest.
     *
     * @param SearchHotelsRequest $request Валидированный запрос поиска.
     * @return JsonResponse Коллекция HotelResource | ошибка 500.
     */
    public function search(SearchHotelsRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Uuid::uuid4()->toString();

        try {
            $hotels = $this->searchHotelsUseCase->execute($request->validated());

            $this->auditLogger->info('Hotels search performed.', [
                'city'           => $request->input('city'),
                'results'        => $hotels->count(),
                'correlation_id' => $correlationId,
                'user_id'        => $request->user()?->getAuthIdentifier(),
            ]);

            return HotelResource::collection($hotels)
                ->response()
                ->header('X-Correlation-ID', $correlationId);

        } catch (\Throwable $e) {
            $this->auditLogger->error('Hotel search failed.', [
                'error'          => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace'          => $e->getTraceAsString(),
            ]);

            return (new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => 'Произошла ошибка при поиске отелей.',
                'correlation_id' => $correlationId,
            ], 500))->header('X-Correlation-ID', $correlationId);
        }
    }

    /**
     * Бронирование номера в отеле.
     *
     * @param BookRoomRequest $request Валидированный запрос бронирования.
     * @return JsonResponse Созданный BookingResource (201) | ошибка 422/500.
     */
    public function book(BookRoomRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Uuid::uuid4()->toString();
        $data          = $request->validated();
        $userId        = (int) $request->user()->getAuthIdentifier();

        try {
            $bookingDto = new BookingDTO(
                roomId:        $data['room_id'],
                userId:        $userId,
                checkInDate:   Carbon::parse($data['check_in_date']),
                checkOutDate:  Carbon::parse($data['check_out_date']),
                guestsCount:   (int) ($data['guests_count'] ?? 1),
                specialRequests: $data['special_requests'] ?? null,
                correlationId: $correlationId,
            );

            $bookingId = $this->bookRoomUseCase->execute($bookingDto);
            $booking   = $this->bookRoomUseCase->getBookingRepository()->find($bookingId);

            $this->auditLogger->info('Room booked successfully.', [
                'booking_id'     => $bookingId->toString(),
                'user_id'        => $userId,
                'room_id'        => $data['room_id'],
                'correlation_id' => $correlationId,
            ]);

            return (new BookingResource($booking))
                ->response()
                ->setStatusCode(201)
                ->header('X-Correlation-ID', $correlationId);

        } catch (\Throwable $e) {
            $this->auditLogger->error('Room booking failed.', [
                'error'          => $e->getMessage(),
                'user_id'        => $userId,
                'correlation_id' => $correlationId,
                'trace'          => $e->getTraceAsString(),
            ]);

            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;

            return (new \Illuminate\Http\JsonResponse([
                'success'        => false,
                'message'        => $e->getMessage() ?: 'Ошибка при бронировании.',
                'correlation_id' => $correlationId,
            ], $statusCode))->header('X-Correlation-ID', $correlationId);
        }
    }
}
