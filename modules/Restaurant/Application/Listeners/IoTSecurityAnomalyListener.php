<?php

declare(strict_types=1);

namespace Modules\Restaurant\Listeners;

use Illuminate\Log\LogManager;
use Modules\Restaurant\Application\Services\IoTSecurityService;
use Modules\Restaurant\Domain\Events\IoTTelemetryReceived;
use Modules\Restaurant\Domain\Enums\IoTSecurityEventType;
use Modules\Restaurant\Domain\Entities\IoTSecurityEvent;
use Modules\Restaurant\Domain\Repositories\IoTSecurityEventRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;
use Carbon\CarbonImmutable;
use Exception;

/**
 * IoTSecurityAnomalyListener
 *
 * CatVRF 2026 Canon - Production Mandatory
 * - Dependency injection instead of facades
 * - Readonly class
 */
final readonly class IoTSecurityAnomalyListener
{
    private const ANOMALY_THRESHOLD = 3; // Number of anomalies before quarantine
    private const TIME_WINDOW_MINUTES = 10; // Time window to count anomalies

    public function __construct(
        private readonly IoTSecurityService $securityService,
        private readonly IoTSecurityEventRepositoryInterface $securityEventRepository,
        private readonly IoTDeviceRepositoryInterface $deviceRepository,
    ) {}

    public function handle(IoTTelemetryReceived $event, LogManager $log): void
    {
        $deviceId = $event->deviceId;
        $telemetryData = $event->telemetryData;

        // Run anomaly detection
        $anomalyMessage = $this->securityService->detectAnomaly($deviceId, $telemetryData);
        
        if ($anomalyMessage === null) {
            return;
        }

        $log->warning('IoT anomaly detected by listener', [
            'device_id' => $deviceId,
            'message' => $anomalyMessage,
        ]);

        // Check if this device has had multiple anomalies recently
        $recentAnomalies = $this->securityEventRepository->findByEventType(
            IoTSecurityEventType::ANOMALY_DETECTED,
            $event->tenantId,
            100
        );

        $deviceAnomalies = array_filter($recentAnomalies, function ($securityEvent) use ($deviceId) {
            return $securityEvent->iotDeviceId === $deviceId 
                && $securityEvent->createdAt->diffInMinutes(CarbonImmutable::now()) <= self::TIME_WINDOW_MINUTES;
        });

        $anomalyCount = count($deviceAnomalies);

        // If threshold exceeded, quarantine the device
        if ($anomalyCount >= self::ANOMALY_THRESHOLD) {
            $log->critical('IoT device quarantined due to multiple anomalies', [
                'device_id' => $deviceId,
                'anomaly_count' => $anomalyCount,
                'time_window_minutes' => self::TIME_WINDOW_MINUTES,
            ]);

            $this->securityService->quarantineDevice(
                $deviceId,
                "Multiple anomalies detected ({$anomalyCount} in " . self::TIME_WINDOW_MINUTES . " minutes)"
            );

            // Log critical security event
            $device = $this->deviceRepository->findById($deviceId);
            if ($device !== null) {
                $securityEvent = IoTSecurityEvent::create(
                    iotDeviceId: $deviceId,
                    tenantId: $device->tenantId,
                    eventType: IoTSecurityEventType::ANOMALY_DETECTED,
                    description: "Device quarantined due to multiple anomalies",
                    eventData: [
                        'anomaly_count' => $anomalyCount,
                        'time_window_minutes' => self::TIME_WINDOW_MINUTES,
                        'last_anomaly' => $anomalyMessage,
                    ],
                    correlationId: $event->correlationId ?? null,
                );

                $this->securityEventRepository->save($securityEvent->withAction('device_quarantined'));
            }
        }
    }
}
