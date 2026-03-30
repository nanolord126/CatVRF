<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiSurgeService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Рассчитать surge multiplier на основе спроса и предложения в зоне.
         */
        public function calculateSurgeMultiplier(
            array $location, // ['lat' => X, 'lng' => Y]
            int $tenantId,
            string $correlationId = ''
        ): float {


            try {
                Log::channel('audit')->info('Calculating surge multiplier', [
                    'location' => $location,
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);
                // - Number of active rides in area
                // - Number of available drivers
                // - Time of day (rush hour, night)
                // - Weather/events
                $multiplier = 1.0;

                // Пример: если много заказов и мало водителей -> surge
                $activeRides = TaxiRide::query()
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'in_progress')
                    ->count();

                if ($activeRides > 100) {
                    $multiplier = 2.5; // 2.5x surge
                } elseif ($activeRides > 50) {
                    $multiplier = 1.75; // 1.75x surge
                } elseif ($activeRides > 20) {
                    $multiplier = 1.25; // 1.25x surge
                }

                return $multiplier;
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Surge multiplier calculation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Рассчитать цену поездки с учётом surge.
         */
        public function calculateRidePrice(
            TaxiRide $ride,
            int $distanceKm,
            string $vehicleClass = 'economy',
            string $correlationId = ''
        ): int {


            $basePrices = [
                'economy' => 5000, // 50 руб за км
                'comfort' => 7500, // 75 руб за км
                'business' => 10000, // 100 руб за км
            ];

            $basePrice = ($basePrices[$vehicleClass] ?? 5000) * $distanceKm;
            $finalPrice = (int) ($basePrice * $ride->surge_multiplier);

            Log::channel('audit')->info('Ride price calculated', [
                'ride_id' => $ride->id,
                'distance_km' => $distanceKm,
                'base_price' => $basePrice,
                'surge_multiplier' => $ride->surge_multiplier,
                'final_price' => $finalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $finalPrice;
        }
}
