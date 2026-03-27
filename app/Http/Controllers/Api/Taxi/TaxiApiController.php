<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Taxi;
use App\Http\Controllers\Controller;
use App\Domains\Taxi\Services\TaxiService;
use App\Domains\Taxi\DTO\RideRequestDto;
use App\Http\Requests\Taxi\RideCreateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * КАНОН 2026: TaxiApiController (Стандарт API).
 * Слой 8: API Слой.
 */
final class TaxiApiController extends Controller
{
    /**
     * Конструктор с инъекцией (по канону).
     */
    public function __construct(
        private readonly TaxiService $taxiService,
    ) {}
    /**
     * Создание новой поездки (Ride POST endpoint).
     * @param RideCreateRequest $request Валидация + Fraud check (Level 8)
     */
    public function create(RideCreateRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-Id', (string)Str::uuid());
        try {
            // 1. Создание DTO (Level 4)
            $dto = RideRequestDto::fromArray($request->validated(), (int)$request->user()->id);
            // 2. Вызов Сервиса (Level 3)
            $ride = $this->taxiService->createRide(
                $request->user(),
                $dto->toServiceArray(),
                $dto->fleetId
            );
            // 3. Возврат JSON (Level 8)
            return response()->json([
                'status' => 'success',
                'data' => [
                    'ride_uuid' => $ride->uuid,
                    'price' => $ride->price,
                    'status' => $ride->status,
                    'estimated_minutes' => $ride->metadata['estimated_minutes'] ?? 0,
                    'surge_multiplier' => $ride->surge_multiplier
                ],
                'correlation_id' => $correlationId
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create taxi ride', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Не удалось создать поездку. Попробуйте позже.',
                'correlation_id' => $correlationId
            ], 500);
        }
    }
    /**
     * Детали текущей поездки (Ride GET endpoint).
     */
    public function show(string $uuid): JsonResponse
    {
        $ride = \App\Domains\Taxi\Models\TaxiRide::where('uuid', $uuid)
            ->where('tenant_id', tenant()->id)
            ->with(['driver', 'vehicle'])
            ->firstOrFail();
        return response()->json([
            'status' => 'success',
            'data' => [
                'uuid' => $ride->uuid,
                'status' => $ride->status,
                'driver' => $ride->driver ? [
                    'name' => $ride->driver->full_name,
                    'rating' => $ride->driver->rating,
                    'car' => $ride->vehicle ? [
                        'brand' => $ride->vehicle->brand,
                        'model' => $ride->vehicle->model,
                        'plate' => $ride->vehicle->license_plate
                    ] : null
                ] : null,
                'price' => $ride->price
            ]
        ]);
    }
    /**
     * Отмена поездки пассажиром.
     */
    public function cancel(string $uuid): JsonResponse
    {
        $ride = \App\Domains\Taxi\Models\TaxiRide::where('uuid', $uuid)
            ->where('passenger_id', auth()->id())
            ->whereIn('status', ['pending', 'accepted'])
            ->firstOrFail();
        $ride->update(['status' => 'cancelled']);
        Log::channel('audit')->info('Taxi ride cancelled by passenger', ['ride_uuid' => $uuid]);
        return response()->json([
            'status' => 'success',
            'message' => 'Поездка успешно отменена.'
        ]);
    }
}
