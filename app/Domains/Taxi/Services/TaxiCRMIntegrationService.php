<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * TaxiCRMIntegrationService - CRM integration for taxi ride status synchronization
 * 
 * Syncs all ride status changes with CRM system for:
 * - Customer journey tracking
 * - Automated follow-up campaigns
 * - Loyalty program integration
 * - Analytics and reporting
 */
final readonly class TaxiCRMIntegrationService
{
    private const CACHE_TTL = 300;
    private const CRM_API_URL = 'https://api.catvrf-crm.com/v1';
    
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    public function syncOrderCreated(TaxiRide $ride, string $correlationId): void
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'taxi_crm_sync_order_created',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $crmData = [
            'event_type' => 'taxi_order_created',
            'event_timestamp' => now()->toIso8601String(),
            'order_id' => $ride->uuid,
            'customer_id' => $ride->passenger_id,
            'tenant_id' => $ride->tenant_id,
            'business_group_id' => $ride->business_group_id,
            'pickup_address' => $ride->pickup_address,
            'dropoff_address' => $ride->dropoff_address,
            'estimated_price' => $ride->total_price,
            'is_b2b' => $ride->business_group_id !== null,
            'metadata' => $ride->metadata,
            'correlation_id' => $correlationId,
        ];

        $this->sendToCRM($crmData, $correlationId);

        $this->audit->log(
            action: 'taxi_crm_order_created_synced',
            subjectType: self::class,
            subjectId: $ride->id,
            oldValues: [],
            newValues: $crmData,
            correlationId: $correlationId,
        );

        $this->logger->info('Taxi order creation synced to CRM', [
            'ride_uuid' => $ride->uuid,
            'passenger_id' => $ride->passenger_id,
            'correlation_id' => $correlationId,
        ]);
    }

    public function syncDriverAssigned(TaxiRide $ride, TaxiDriver $driver, string $correlationId): void
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'taxi_crm_sync_driver_assigned',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $crmData = [
            'event_type' => 'taxi_driver_assigned',
            'event_timestamp' => now()->toIso8601String(),
            'order_id' => $ride->uuid,
            'customer_id' => $ride->passenger_id,
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'driver_rating' => $driver->rating,
            'vehicle_plate' => $driver->vehicles->first()?->plate_number,
            'predicted_eta' => $ride->predicted_eta,
            'correlation_id' => $correlationId,
        ];

        $this->sendToCRM($crmData, $correlationId);

        $this->audit->log(
            action: 'taxi_crm_driver_assigned_synced',
            subjectType: self::class,
            subjectId: $ride->id,
            oldValues: [],
            newValues: $crmData,
            correlationId: $correlationId,
        );

        $this->logger->info('Driver assignment synced to CRM', [
            'ride_uuid' => $ride->uuid,
            'driver_id' => $driver->id,
            'correlation_id' => $correlationId,
        ]);
    }

    public function syncRideStarted(TaxiRide $ride, string $correlationId): void
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'taxi_crm_sync_ride_started',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $crmData = [
            'event_type' => 'taxi_ride_started',
            'event_timestamp' => now()->toIso8601String(),
            'order_id' => $ride->uuid,
            'customer_id' => $ride->passenger_id,
            'driver_id' => $ride->driver_id,
            'started_at' => $ride->started_at,
            'correlation_id' => $correlationId,
        ];

        $this->sendToCRM($crmData, $correlationId);

        $this->audit->log(
            action: 'taxi_crm_ride_started_synced',
            subjectType: self::class,
            subjectId: $ride->id,
            oldValues: [],
            newValues: $crmData,
            correlationId: $correlationId,
        );

        $this->logger->info('Ride start synced to CRM', [
            'ride_uuid' => $ride->uuid,
            'driver_id' => $ride->driver_id,
            'correlation_id' => $correlationId,
        ]);
    }

    public function syncRideCompleted(TaxiRide $ride, string $correlationId): void
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'taxi_crm_sync_ride_completed',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $crmData = [
            'event_type' => 'taxi_ride_completed',
            'event_timestamp' => now()->toIso8601String(),
            'order_id' => $ride->uuid,
            'customer_id' => $ride->passenger_id,
            'driver_id' => $ride->driver_id,
            'completed_at' => $ride->completed_at,
            'final_price' => $ride->final_price,
            'actual_distance_km' => $ride->actual_distance_km,
            'rating' => $ride->rating,
            'correlation_id' => $correlationId,
        ];

        $this->sendToCRM($crmData, $correlationId);

        $this->audit->log(
            action: 'taxi_crm_ride_completed_synced',
            subjectType: self::class,
            subjectId: $ride->id,
            oldValues: [],
            newValues: $crmData,
            correlationId: $correlationId,
        );

        $this->logger->info('Ride completion synced to CRM', [
            'ride_uuid' => $ride->uuid,
            'final_price' => $ride->final_price,
            'correlation_id' => $correlationId,
        ]);
    }

    public function syncRideCancelled(TaxiRide $ride, string $correlationId): void
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'taxi_crm_sync_ride_cancelled',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $crmData = [
            'event_type' => 'taxi_ride_cancelled',
            'event_timestamp' => now()->toIso8601String(),
            'order_id' => $ride->uuid,
            'customer_id' => $ride->passenger_id,
            'driver_id' => $ride->driver_id,
            'cancelled_at' => $ride->cancelled_at,
            'cancelled_by' => $ride->cancelled_by,
            'cancellation_reason' => $ride->cancellation_reason,
            'cancellation_fee' => $ride->cancellation_fee,
            'correlation_id' => $correlationId,
        ];

        $this->sendToCRM($crmData, $correlationId);

        $this->audit->log(
            action: 'taxi_crm_ride_cancelled_synced',
            subjectType: self::class,
            subjectId: $ride->id,
            oldValues: [],
            newValues: $crmData,
            correlationId: $correlationId,
        );

        $this->logger->info('Ride cancellation synced to CRM', [
            'ride_uuid' => $ride->uuid,
            'cancelled_by' => $ride->cancelled_by,
            'reason' => $ride->cancellation_reason,
            'correlation_id' => $correlationId,
        ]);
    }

    private function sendToCRM(array $data, string $correlationId): void
    {
        try {
            $response = Http::timeout(5)->post(
                self::CRM_API_URL . '/events',
                $data,
                [
                    'Authorization' => 'Bearer ' . config('services.crm.api_key'),
                    'X-Correlation-ID' => $correlationId,
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response->successful()) {
                $this->logger->warning('CRM sync failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'correlation_id' => $correlationId,
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('CRM sync error', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }
}
