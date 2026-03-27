<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Courier;
use App\Models\DeliveryOrder;
use App\Services\FraudControlService;
use App\Services\Security\RateLimiterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Throwable;

final readonly class CourierService
{
    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly RateLimiterService $rateLimiter,
    ) {}

    /**
     * Регистрирует курьера в системе.
     *
     * @param int $tenantId ID тенанта
     * @param array $data {name, phone, vehicle_type, license_number}
     * @param string $correlationId Идентификатор корреляции
     * @return array{courierId: int, status: string}
     * @throws Exception
     */
    public function registerCourier(
        int $tenantId,
        array $data,
        string $correlationId = '',
    ): array {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        try {
            // Фрод-проверка
            $this->fraudControl->check('courier_register', [
                'tenant_id' => $tenantId,
                'phone' => $data['phone'] ?? '',
            ], $correlationId);

            // Rate limiting
            if (!$this->rateLimiter->allowTenant($tenantId, 'courier:register', 50, 3600)) {
                throw new Exception('Rate limit exceeded for courier registration', 429);
            }

            Log::channel('audit')->info('Courier registration started', [
                'tenant_id' => $tenantId,
                'name' => $data['name'] ?? '',
                'correlation_id' => $correlationId,
            ]);

            $result = DB::transaction(function () use ($tenantId, $data, $correlationId) {
                $courier = Courier::create([
                    'tenant_id' => $tenantId,
                    'uuid' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'vehicle_type' => $data['vehicle_type'] ?? 'bike',
                    'license_number' => $data['license_number'],
                    'correlation_id' => $correlationId,
                    'status' => 'pending_verification',
                    'rating' => 5.0,
                    'completed_deliveries' => 0,
                    'tags' => ['courier:new', 'source:register'],
                ]);

                Log::channel('audit')->info('Courier registered', [
                    'tenant_id' => $tenantId,
                    'courier_id' => $courier->id,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'courierId' => $courier->id,
                    'status' => 'pending_verification',
                ];
            });

            return $result;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Courier registration failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Получает текущее местоположение курьера (GPS).
     *
     * @param int $courierId ID курьера
     * @param string $correlationId Идентификатор корреляции
     * @return array{lat: float, lon: float, last_updated: string}
     * @throws Exception
     */
    public function getCurrentLocation(
        int $courierId,
        string $correlationId = '',
    ): array {
        try {
            $courier = Courier::findOrFail($courierId);

            return [
                'lat' => $courier->current_lat ?? 55.75,
                'lon' => $courier->current_lon ?? 37.62,
                'last_updated' => $courier->last_location_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        } catch (Throwable $e) {
            Log::channel('audit')->error('Courier location request failed', [
                'courier_id' => $courierId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Назначает доставку курьеру.
     *
     * @param int $courierId ID курьера
     * @param int $deliveryOrderId ID заказа доставки
     * @param string $correlationId Идентификатор корреляции
     * @return array{deliveryId: int, status: string, estimated_time: string}
     * @throws Exception
     */
    public function assignDelivery(
        int $courierId,
        int $deliveryOrderId,
        string $correlationId = '',
    ): array {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        try {
            // Фрод-проверка
            $this->fraudControl->check('delivery_assign', [
                'courier_id' => $courierId,
                'delivery_order_id' => $deliveryOrderId,
            ], $correlationId);

            Log::channel('audit')->info('Delivery assignment started', [
                'courier_id' => $courierId,
                'delivery_order_id' => $deliveryOrderId,
                'correlation_id' => $correlationId,
            ]);

            $result = DB::transaction(function () use ($courierId, $deliveryOrderId, $correlationId) {
                $delivery = DeliveryOrder::findOrFail($deliveryOrderId);
                $delivery->update([
                    'courier_id' => $courierId,
                    'status' => 'assigned',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Delivery assigned', [
                    'delivery_order_id' => $deliveryOrderId,
                    'courier_id' => $courierId,
                    'correlation_id' => $correlationId,
                ]);

                return [
                    'deliveryId' => $deliveryOrderId,
                    'status' => 'assigned',
                    'estimated_time' => now()->addMinutes(25)->toIso8601String(),
                ];
            });

            return $result;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Delivery assignment failed', [
                'courier_id' => $courierId,
                'delivery_order_id' => $deliveryOrderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Завершает доставку и обновляет рейтинг курьера.
     *
     * @param int $courierId ID курьера
     * @param int $deliveryOrderId ID заказа доставки
     * @param int $ratingScore Оценка 1-5
     * @param string $correlationId Идентификатор корреляции
     * @return bool
     * @throws Exception
     */
    public function completeDelivery(
        int $courierId,
        int $deliveryOrderId,
        int $ratingScore = 5,
        string $correlationId = '',
    ): bool {
        $correlationId = $correlationId ?: (string) Str::uuid()->toString();

        try {
            // Фрод-проверка
            $this->fraudControl->check('delivery_complete', [
                'courier_id' => $courierId,
                'rating' => $ratingScore,
            ], $correlationId);

            Log::channel('audit')->info('Delivery completion started', [
                'courier_id' => $courierId,
                'delivery_order_id' => $deliveryOrderId,
                'rating' => $ratingScore,
                'correlation_id' => $correlationId,
            ]);

            DB::transaction(function () use ($courierId, $deliveryOrderId, $ratingScore, $correlationId) {
                $delivery = DeliveryOrder::findOrFail($deliveryOrderId);
                $delivery->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'correlation_id' => $correlationId,
                ]);

                // Обновляем рейтинг курьера
                $courier = Courier::findOrFail($courierId);
                $newRating = (($courier->rating * $courier->completed_deliveries) + $ratingScore) 
                    / ($courier->completed_deliveries + 1);
                $courier->update([
                    'rating' => $newRating,
                    'completed_deliveries' => $courier->completed_deliveries + 1,
                ]);

                Log::channel('audit')->info('Delivery completed', [
                    'delivery_order_id' => $deliveryOrderId,
                    'courier_id' => $courierId,
                    'courier_new_rating' => $newRating,
                    'correlation_id' => $correlationId,
                ]);
            });

            return true;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Delivery completion failed', [
                'courier_id' => $courierId,
                'delivery_order_id' => $deliveryOrderId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
