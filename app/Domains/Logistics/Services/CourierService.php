<?php declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class CourierService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Регистрация нового курьера на платформе.
     */
    public function registerCourier(
        int $tenantId,
        int $userId,
        string $fullName,
        string $vehicleType,
        string $phoneNumber,
        string $correlationId,
    ): Courier {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'courier_register',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($tenantId, $userId, $fullName, $vehicleType, $phoneNumber, $correlationId): Courier {
            $courier = Courier::create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'full_name' => $fullName,
                'vehicle_type' => $vehicleType,
                'phone_number' => $phoneNumber,
                'rating' => 5.0,
                'is_available' => false,
                'is_active' => true,
                'current_location' => [],
                'correlation_id' => $correlationId,
                'tags' => ['registered_via' => 'platform'],
            ]);

            $this->logger->info('Courier registered', [
                'courier_id' => $courier->id,
                'courier_uuid' => $courier->uuid,
                'tenant_id' => $tenantId,
                'vehicle_type' => $vehicleType,
                'correlation_id' => $correlationId,
            ]);

            return $courier;
        });
    }

    /**
     * Перевод курьера в онлайн-статус (готов принимать заказы).
     */
    public function goOnline(int $courierId, float $lat, float $lon, string $correlationId): Courier
    {
        return $this->db->transaction(function () use ($courierId, $lat, $lon, $correlationId): Courier {
            $courier = Courier::where('id', $courierId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $courier->update([
                'is_available' => true,
                'current_location' => ['lat' => $lat, 'lon' => $lon],
                'last_location_update' => now(),
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Courier went online', [
                'courier_id' => $courier->id,
                'lat' => $lat,
                'lon' => $lon,
                'correlation_id' => $correlationId,
            ]);

            return $courier->refresh();
        });
    }

    /**
     * Перевод курьера в офлайн-статус.
     */
    public function goOffline(int $courierId, string $correlationId): Courier
    {
        return $this->db->transaction(function () use ($courierId, $correlationId): Courier {
            $courier = Courier::lockForUpdate()->findOrFail($courierId);

            $courier->update([
                'is_available' => false,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Courier went offline', [
                'courier_id' => $courier->id,
                'correlation_id' => $correlationId,
            ]);

            return $courier->refresh();
        });
    }

    /**
     * Обновление геопозиции курьера (вызывается каждые 3 секунды при доставке).
     */
    public function updateLocation(int $courierId, float $lat, float $lon, float $speed, string $correlationId): void
    {
        $this->db->transaction(function () use ($courierId, $lat, $lon, $speed, $correlationId): void {
            Courier::where('id', $courierId)->update([
                'current_location' => ['lat' => $lat, 'lon' => $lon],
                'last_location_update' => now(),
            ]);

            DeliveryTrack::create([
                'courier_id' => $courierId,
                'lat' => $lat,
                'lon' => $lon,
                'speed' => $speed,
                'correlation_id' => $correlationId,
                'tracked_at' => now(),
            ]);
        });
    }

    /**
     * Обновление рейтинга курьера после завершения доставки.
     */
    public function updateRating(int $courierId, float $newRating, string $correlationId): Courier
    {
        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'courier_rating_update',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($courierId, $newRating, $correlationId): Courier {
            $courier = Courier::lockForUpdate()->findOrFail($courierId);

            $totalDeliveries = $courier->total_deliveries + 1;
            $weightedRating = (($courier->rating * $courier->total_deliveries) + $newRating) / $totalDeliveries;

            $courier->update([
                'rating' => round($weightedRating, 2),
                'total_deliveries' => $totalDeliveries,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->info('Courier rating updated', [
                'courier_id' => $courier->id,
                'new_rating' => round($weightedRating, 2),
                'total_deliveries' => $totalDeliveries,
                'correlation_id' => $correlationId,
            ]);

            return $courier->refresh();
        });
    }
}
