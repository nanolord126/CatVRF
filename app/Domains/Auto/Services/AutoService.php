<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class AutoService
{
    public function __construct(private \App\Services\FraudControlService $fraud,
        private \App\Services\AuditService $audit,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    /**
         * Регистрация нового ТС в системе.
         */
        public function registerVehicle(array $data, string $correlationId): Vehicle
        {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            return $this->db->transaction(function () use ($data, $correlationId) {
                $data['correlation_id'] = $correlationId;
                $data['uuid'] = (string) Str::uuid();
                $data['status'] = 'active';

                $vehicle = Vehicle::create($data);

                $this->logger->info('Vehicle registered', [
                    'uuid' => $vehicle->uuid,
                    'correlation_id' => $correlationId,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'license_plate' => $vehicle->license_plate,
                ]);

                return $vehicle;
            });
        }

        /**
         * Смена статуса (Ремонт, Свободен, В пути).
         */
        public function updateStatus(Vehicle $vehicle, string $newStatus, string $correlationId): void
        {
            $this->db->transaction(function () use ($vehicle, $newStatus, $correlationId) {
                $oldStatus = $vehicle->status;
                $vehicle->update([
                    'status' => $newStatus,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Vehicle status changed', [
                    'uuid' => $vehicle->uuid,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'correlation_id' => $correlationId,
                ]);
            });
        }

        /**
         * Передача ТС в автопарк (B2B).
         */
        public function assignToFleet(Vehicle $vehicle, int $fleetId, string $correlationId): void
        {
            $this->db->transaction(function () use ($vehicle, $fleetId, $correlationId) {
                $vehicle->update([
                    'business_group_id' => $fleetId,
                    'type' => 'fleet',
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Vehicle assigned to fleet', [
                    'uuid' => $vehicle->uuid,
                    'fleet_id' => $fleetId,
                    'correlation_id' => $correlationId,
                ]);
            });
        }
}
