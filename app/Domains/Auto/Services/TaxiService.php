<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class TaxiService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Создание поездки (Booking).
         */
        public function createRide(int $passengerId, array $data, string $correlationId): TaxiRide
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            return $this->db->transaction(function () use ($passengerId, $data, $correlationId) {
                $data['uuid'] = (string) Str::uuid();
                $data['tenant_id'] = tenant()->id;
                $data['passenger_id'] = $passengerId;
                $data['status'] = 'searching';
                $data['correlation_id'] = $correlationId;

                $ride = TaxiRide::create($data);

                $this->logger->info('Taxi ride searching driver', [
                    'ride_uuid' => $ride->uuid,
                    'passenger_id' => $passengerId,
                    'pickup' => $data['pickup_point'] ?? 'N/A',
                    'correlation_id' => $correlationId,
                ]);

                return $ride;
            });
        }

        /**
         * Назначение водителя (Accepting).
         */
        public function assignDriver(TaxiRide $ride, Vehicle $vehicle, int $driverId, string $correlationId): void
        {
            $this->db->transaction(function () use ($ride, $vehicle, $driverId, $correlationId) {
                $ride->update([
                    'driver_id' => $driverId,
                    'vehicle_id' => $vehicle->id,
                    'status' => 'accepted',
                    'correlation_id' => $correlationId,
                    'accepted_at' => Carbon::now(),
                ]);

                // Резервация автомобиля под поездку
                $vehicle->update(['status' => 'busy']);

                $this->logger->info('Taxi ride accepted', [
                    'ride_uuid' => $ride->uuid,
                    'vehicle_uuid' => $vehicle->uuid,
                    'driver_id' => $driverId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Завершение поездки.
         */
        public function completeRide(TaxiRide $ride, string $correlationId): void
        {
            $this->db->transaction(function () use ($ride, $correlationId) {
                $ride->update([
                    'status' => 'finished',
                    'finished_at' => Carbon::now(),
                    'correlation_id' => $correlationId,
                ]);

                // Освобождение автомобиля
                if ($ride->vehicle) {
                    $ride->vehicle->update(['status' => 'active']);
                }

                $this->logger->info('Taxi ride completed', [
                    'ride_uuid' => $ride->uuid,
                    'final_cost' => $ride->total_cost_kopecks,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Отмена заказа такси (idempotent).
         */
        public function cancelRide(TaxiRide $ride, string $reason, string $correlationId): void
        {
            $this->db->transaction(function () use ($ride, $reason, $correlationId) {
                if (in_array($ride->status, ['finished', 'cancelled'])) {
                    return;
                }

                $ride->update([
                    'status' => 'cancelled',
                    'cancel_reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                if ($ride->vehicle) {
                    $ride->vehicle->update(['status' => 'active']);
                }

                $this->logger->warning('Taxi ride cancelled', [
                    'ride_uuid' => $ride->uuid,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
