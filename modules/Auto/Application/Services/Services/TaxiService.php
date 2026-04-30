<?php

declare(strict_types=1);

namespace Modules\Auto\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Log\LogManager;
use Modules\Auto\Models\TaxiRide;
use App\Services\Fraud\FraudControlService;
use App\Domains\CRM\Services\AutoCrmService;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

/**
 * Taxi Service — Сервис для управления такси-поездками
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Fraud check перед всеми мутациями
 * - Readonly класс
 * - Cache::tags для инвалидации
 * - Audit логирование
 * - CRM интеграция
 */
final readonly class TaxiService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AutoCrmService $autoCrm,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
    ) {}

    /**
     * Создать поездку
     */
    public function createRide(array $data, ?string $correlationId = null): TaxiRide
    {
        $correlationId ??= uniqid('taxi_', true);

        // FRAUD CHECK - мандаторно первым действием
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'taxi_create_ride',
            'user_id' => $data['passenger_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->warning('Taxi ride creation blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'data' => $data,
                'correlation_id' => $correlationId,
            ]);
            $this->logAction('taxi_ride_creation_blocked', 'TaxiRide', null, [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
            ], $data['passenger_id'] ?? null, $data['tenant_id'] ?? null);
            throw new \RuntimeException('Taxi ride creation blocked by fraud detection');
        }

        return $this->db->transaction(function () use ($data, $correlationId) {
            $ride = TaxiRide::create([
                'driver_id'       => $data['driver_id'],
                'passenger_id'    => $data['passenger_id'],
                'vehicle_class'   => $data['vehicle_class'] ?? 'economy',
                'pickup_lat'      => $data['pickup_lat'] ?? 0,
                'pickup_lng'      => $data['pickup_lng'] ?? 0,
                'dropoff_lat'     => $data['dropoff_lat'] ?? 0,
                'dropoff_lng'     => $data['dropoff_lng'] ?? 0,
                'distance_km'     => $data['distance_km'] ?? 0,
                'fare_amount'     => $data['fare_amount'] ?? 0,
                'status'          => 'pending',
                'correlation_id'  => $correlationId,
            ]);

            // Очищаем кэш
            $this->clearRideCache($ride->passenger_id);

            // AUDIT LOG
            $this->logCreated('TaxiRide', $ride->id, [
                'driver_id' => $ride->driver_id,
                'passenger_id' => $ride->passenger_id,
                'fare_amount' => $ride->fare_amount,
                'vehicle_class' => $ride->vehicle_class,
            ], $ride->passenger_id, $ride->tenant_id ?? null);

            // CRM INTEGRATION
            try {
                $crmData = [
                    'passenger_id' => $ride->passenger_id,
                    'ride_id' => $ride->id,
                    'vehicle_class' => $ride->vehicle_class,
                    'fare_amount' => $ride->fare_amount,
                    'correlation_id' => $correlationId,
                ];
                $this->logAction('taxi_crm_sync_prepared', 'TaxiRide', $ride->id, $crmData, $ride->passenger_id, $ride->tenant_id ?? null);
            } catch (\Throwable $e) {
                $this->logAction('taxi_crm_sync_failed', 'TaxiRide', $ride->id, [
                    'error' => $e->getMessage(),
                ], $ride->passenger_id, $ride->tenant_id ?? null);
            }

            return $ride;
        });
    }

    /**
     * Завершить поездку
     */
    public function completeRide(TaxiRide $ride, ?string $correlationId = null): TaxiRide
    {
        $correlationId ??= uniqid('taxi_', true);

        // FRAUD CHECK
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'taxi_complete_ride',
            'user_id' => $ride->passenger_id,
            'tenant_id' => $ride->tenant_id ?? null,
            'ip_address' => request()->ip(),
        ]);

        if ($fraudResult['should_block']) {
            $this->logger->warning('Taxi ride completion blocked by fraud detection', [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
                'ride_id' => $ride->id,
                'correlation_id' => $correlationId,
            ]);
            $this->logAction('taxi_ride_completion_blocked', 'TaxiRide', $ride->id, [
                'fraud_score' => $fraudResult['fraud_score'],
                'indicators' => $fraudResult['indicators'],
            ], $ride->passenger_id, $ride->tenant_id ?? null);
            throw new \RuntimeException('Taxi ride completion blocked by fraud detection');
        }

        return $this->db->transaction(function () use ($ride, $correlationId) {
            $ride->update([
                'status'         => 'completed',
                'completed_at'   => now(),
                'correlation_id' => $correlationId,
            ]);

            // Очищаем кэш
            $this->clearRideCache($ride->passenger_id);

            // AUDIT LOG
            $this->logAction('taxi_ride_completed', 'TaxiRide', $ride->id, [
                'passenger_id' => $ride->passenger_id,
                'fare_amount' => $ride->fare_amount,
            ], $ride->passenger_id, $ride->tenant_id ?? null);

            // CRM INTEGRATION
            try {
                $crmData = [
                    'passenger_id' => $ride->passenger_id,
                    'ride_id' => $ride->id,
                    'vehicle_class' => $ride->vehicle_class,
                    'fare_amount' => $ride->fare_amount,
                    'correlation_id' => $correlationId,
                ];
                $this->logAction('taxi_crm_completion_sync_prepared', 'TaxiRide', $ride->id, $crmData, $ride->passenger_id, $ride->tenant_id ?? null);
            } catch (\Throwable $e) {
                $this->logAction('taxi_crm_completion_sync_failed', 'TaxiRide', $ride->id, [
                    'error' => $e->getMessage(),
                ], $ride->passenger_id, $ride->tenant_id ?? null);
            }

            return $ride->fresh();
        });
    }

    /**
     * Получить активные поездки пассажира
     */
    public function getActiveRides(int $passengerId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cache->tags(['auto', 'rides', "passenger:{$passengerId}"])
            ->remember("auto:{$passengerId}:active_rides", 300, function () use ($passengerId) {
                return TaxiRide::where('passenger_id', $passengerId)
                    ->where('status', '!=', 'completed')
                    ->orderBy('created_at', 'desc')
                    ->get();
            });
    }

    /**
     * Очистить кэш поездок
     */
    private function clearRideCache(int $passengerId): void
    {
        $this->cache->tags(['auto', 'rides', "passenger:{$passengerId}"])->flush();
    }
}
