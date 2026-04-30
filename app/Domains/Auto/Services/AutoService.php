<?php

declare(strict_types=1);

namespace App\Domains\Auto\Services;

use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Domains\Shared\Realtime\RealtimeTrackingAdapter;
use Illuminate\Database\DatabaseManager;

final readonly class AutoService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly RealtimeTrackingAdapter $trackingAdapter,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard
    ) {}

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

            $this->logger->$this->logger->info('Vehicle registered', [
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

            $this->logger->$this->logger->info('Vehicle status changed', [
                'uuid' => $vehicle->uuid,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'correlation_id' => $correlationId,
            ]);

            // Запуск реалтайм-трекинга при статусе "В пути"
            if ($newStatus === 'in_transit') {
                $this->trackingAdapter->startTracking([
                    'order_id' => $vehicle->id,
                    'vertical' => 'auto',
                    'sub_vertical' => null,
                    'courier_id' => $vehicle->driver_id ?? null,
                    'buyer_id' => null,
                ], $correlationId);
            }
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

            $this->logger->$this->logger->info('Vehicle assigned to fleet', [
                'uuid' => $vehicle->uuid,
                'fleet_id' => $fleetId,
                'correlation_id' => $correlationId,
            ]);
        });
    }
}
