<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class WashService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    /**
         * Создание записи на мойку.
         */
        public function bookWash(int $clientId, Vehicle $vehicle, array $data, string $correlationId): WashBooking
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            return $this->db->transaction(function () use ($clientId, $vehicle, $data, $correlationId) {
                $data['uuid'] = (string) Str::uuid();
                $data['tenant_id'] = tenant()->id;
                $data['vehicle_id'] = $vehicle->id;
                $data['client_id'] = $clientId;
                $data['status'] = 'pending';
                $data['correlation_id'] = $correlationId;

                $booking = WashBooking::create($data);

                $this->logger->info('Wash booking created', [
                    'booking_uuid' => $booking->uuid,
                    'vehicle_uuid' => $vehicle->uuid,
                    'client_id' => $clientId,
                    'correlation_id' => $correlationId,
                ]);

                return $booking;
            });
        }

        /**
         * Начало мойки.
         */
        public function startWash(WashBooking $booking, string $correlationId): void
        {
            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    'status' => 'active',
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Wash service started', [
                    'booking_uuid' => $booking->uuid,
                    'vehicle_uuid' => $booking->vehicle->uuid,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Завершение мойки.
         */
        public function finishWash(WashBooking $booking, string $correlationId): void
        {
            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    'status' => 'completed',
                    'finished_at' => Carbon::now(),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Wash service completed', [
                    'booking_uuid' => $booking->uuid,
                    'final_price' => $booking->price_kopecks,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Отмена брони (idempotent).
         */
        public function cancelBooking(WashBooking $booking, string $correlationId): void
        {
            if (in_array($booking->status, ['completed', 'cancelled'])) {
                return;
            }

            $this->db->transaction(function () use ($booking, $correlationId) {
                $booking->update([
                    'status' => 'cancelled',
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->warning('Wash booking cancelled', [
                    'booking_uuid' => $booking->uuid,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
