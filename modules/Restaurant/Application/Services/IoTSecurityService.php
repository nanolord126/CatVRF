<?php

declare(strict_types=1);

namespace Modules\Restaurant\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Restaurant\Domain\Entities\IoTDevice;
use Modules\Restaurant\Domain\Repositories\IoTDeviceRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\DeviceCertificateRepositoryInterface;
use Modules\Restaurant\Domain\Repositories\IoTSecurityEventRepositoryInterface;
use Modules\Restaurant\Domain\Enums\IoTSecurityEventType;
use Modules\Restaurant\Domain\Enums\IoTSecurityStatus;
use Carbon\CarbonImmutable;
use Exception;

final class IoTSecurityService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const QUARANTINE_CACHE_TTL = 3600; // 1 hour
    private const RATE_LIMIT_WINDOW = 60; // 1 minute
    private const NONCE_TTL = 300; // 5 minutes for replay protection

    public function __construct(
        private readonly IoTDeviceRepositoryInterface $deviceRepository,
        private readonly DeviceCertificateRepositoryInterface $certificateRepository,
        private readonly IoTSecurityEventRepositoryInterface $securityEventRepository,
    ) {}

    /**
     * Verify device signature and certificate with replay protection
     */
    public function verifyDeviceAndSignature(
        string $deviceIdentifier,
        string $signature,
        string $signableData,
        ?string $certificateFingerprint = null,
        ?string $nonce = null,
    ): ?IoTDevice {
        $device = $this->deviceRepository->findByIdentifier($deviceIdentifier);
        
        if ($device === null) {
            $this->logSecurityEvent(
                null,
                $deviceIdentifier,
                IoTSecurityEventType::DEVICE_SPOOFING,
                'Device not found in registry',
                ['identifier' => $deviceIdentifier],
            );
            return null;
        }

        // Check for replay attack using nonce
        if ($nonce !== null) {
            if (!$this->validateNonce($device->id, $nonce)) {
                $this->logSecurityEvent(
                    $device->id,
                    $deviceIdentifier,
                    IoTSecurityEventType::REPLAY_ATTACK,
                    'Replay attack detected: duplicate nonce',
                    ['nonce' => $nonce],
                );
                $this->quarantineDevice($device->id, 'Replay attack detected');
                return null;
            }
        }

        // Check if device is quarantined
        if ($device->securityStatus === IoTSecurityStatus::QUARANTINED || 
            $device->securityStatus === IoTSecurityStatus::COMPROMISED) {
            $this->logSecurityEvent(
                $device->id,
                $deviceIdentifier,
                IoTSecurityEventType::UNAUTHORIZED_ACCESS,
                'Attempted access from quarantined device',
                ['security_status' => $device->securityStatus->value],
            );
            return null;
        }

        // Check if device is active
        if (!$device->isActive) {
            $this->logSecurityEvent(
                $device->id,
                $deviceIdentifier,
                IoTSecurityEventType::UNAUTHORIZED_ACCESS,
                'Attempted access from disabled device',
                [],
            );
            return null;
        }

        // Check rate limit
        if (!$this->checkRateLimit($device->id)) {
            $this->logSecurityEvent(
                $device->id,
                $deviceIdentifier,
                IoTSecurityEventType::RATE_LIMIT_EXCEEDED,
                'Device exceeded rate limit',
                ['rate_limit' => $device->metadata['rate_limit_per_minute'] ?? 60],
            );
            return null;
        }

        // Verify certificate fingerprint if provided
        if ($certificateFingerprint !== null) {
            if (!$this->verifyCertificateFingerprint($device->id, $certificateFingerprint)) {
                $this->logSecurityEvent(
                    $device->id,
                    $deviceIdentifier,
                    IoTSecurityEventType::CERTIFICATE_REVOKED,
                    'Invalid or revoked certificate fingerprint',
                    ['fingerprint' => $certificateFingerprint],
                );
                $this->quarantineDevice($device->id, 'Invalid certificate');
                return null;
            }
        }

        // Verify signature
        if (!$this->verifySignature($device->id, $signature, $signableData)) {
            $this->logSecurityEvent(
                $device->id,
                $deviceIdentifier,
                IoTSecurityEventType::SIGNATURE_VERIFICATION_FAILED,
                'Signature verification failed',
                ['signature' => substr($signature, 0, 32) . '...'],
            );
            $this->quarantineDevice($device->id, 'Signature verification failed');
            return null;
        }

        $this->logSecurityEvent(
            $device->id,
            $deviceIdentifier,
            IoTSecurityEventType::AUTHENTICATION_SUCCESS,
            'Device authenticated successfully',
            [],
        );

        return $device;
    }

    /**
     * Quarantine a device due to security concerns
     */
    public function quarantineDevice(int $deviceId, string $reason): void
    {
        $device = $this->deviceRepository->findById($deviceId);
        if ($device === null) {
            return;
        }

        // Update device security status
        $updatedDevice = $device->withSecurityStatus(IoTSecurityStatus::QUARANTINED);
        $this->deviceRepository->save($updatedDevice);

        // Cache quarantine status
        Cache::put("iot_quarantine:{$deviceId}", true, self::QUARANTINE_CACHE_TTL);

        // Log security event
        $this->logSecurityEvent(
            $deviceId,
            $device->deviceIdentifier,
            IoTSecurityEventType::QUARANTINE_TRIGGERED,
            "Device quarantined: {$reason}",
            ['reason' => $reason],
        );

        Log::warning('IoT device quarantined', [
            'device_id' => $deviceId,
            'identifier' => $device->deviceIdentifier,
            'reason' => $reason,
        ]);
    }

    /**
     * Lift quarantine from a device
     */
    public function liftQuarantine(int $deviceId, string $reason): void
    {
        $device = $this->deviceRepository->findById($deviceId);
        if ($device === null) {
            return;
        }

        // Update device security status
        $updatedDevice = $device->withSecurityStatus(IoTSecurityStatus::ACTIVE);
        $this->deviceRepository->save($updatedDevice);

        // Clear quarantine cache
        Cache::forget("iot_quarantine:{$deviceId}");

        // Log security event
        $this->logSecurityEvent(
            $deviceId,
            $device->deviceIdentifier,
            IoTSecurityEventType::QUARANTINE_LIFTED,
            "Device quarantine lifted: {$reason}",
            ['reason' => $reason],
        );

        Log::info('IoT device quarantine lifted', [
            'device_id' => $deviceId,
            'identifier' => $device->deviceIdentifier,
            'reason' => $reason,
        ]);
    }

    /**
     * Check if device is quarantined
     */
    public function isQuarantined(int $deviceId): bool
    {
        return Cache::get("iot_quarantine:{$deviceId}", false);
    }

    /**
     * Check rate limit for device
     */
    private function checkRateLimit(int $deviceId): bool
    {
        $key = "iot_rate_limit:{$deviceId}";
        $current = Redis::incr($key);
        
        if ($current === 1) {
            Redis::expire($key, self::RATE_LIMIT_WINDOW);
        }

        $device = $this->deviceRepository->findById($deviceId);
        $limit = $device?->metadata['rate_limit_per_minute'] ?? 60;

        return $current <= $limit;
    }

    /**
     * Verify certificate fingerprint
     */
    private function verifyCertificateFingerprint(int $deviceId, string $fingerprint): bool
    {
        $cacheKey = "iot_cert_fingerprint:{$deviceId}:{$fingerprint}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($deviceId, $fingerprint) {
            $certificate = $this->certificateRepository->findByFingerprint($fingerprint);
            
            if ($certificate === null) {
                return false;
            }
            
            // Verify certificate belongs to the device
            if ($certificate->iotDeviceId !== $deviceId) {
                Log::warning('Certificate fingerprint does not match device', [
                    'device_id' => $deviceId,
                    'certificate_device_id' => $certificate->iotDeviceId,
                ]);
                return false;
            }
            
            // Check if certificate is valid (not revoked, not expired)
            return $certificate->isValid();
        });
    }

    /**
     * Verify signature using OpenSSL
     */
    private function verifySignature(int $deviceId, string $signature, string $data): bool
    {
        $cacheKey = "iot_signature:{$deviceId}:" . hash('sha256', $signature . $data);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($deviceId, $signature, $data) {
            // Get active certificate for device
            $certificate = $this->certificateRepository->findActiveByDeviceId($deviceId);
            
            if ($certificate === null) {
                Log::warning('No active certificate found for device', ['device_id' => $deviceId]);
                return false;
            }
            
            // Decode signature from base64
            $decodedSignature = base64_decode($signature);
            if ($decodedSignature === false) {
                Log::warning('Invalid signature encoding', ['device_id' => $deviceId]);
                return false;
            }
            
            // Verify signature using OpenSSL
            $publicKey = $certificate->publicKey;
            $result = openssl_verify($data, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
            
            if ($result === -1) {
                Log::error('OpenSSL signature verification error', [
                    'device_id' => $deviceId,
                    'error' => openssl_error_string(),
                ]);
                return false;
            }
            
            return $result === 1;
        });
    }

    /**
     * Log security event to database and logs
     */
    private function logSecurityEvent(
        ?int $deviceId,
        string $deviceIdentifier,
        IoTSecurityEventType $eventType,
        string $description,
        array $eventData,
        ?string $sourceIp = null,
        ?string $correlationId = null,
    ): void {
        $severity = $eventType->getSeverity();
        
        // Log to Laravel logs
        Log::log($severity, 'IoT Security Event', [
            'device_id' => $deviceId,
            'device_identifier' => $deviceIdentifier,
            'event_type' => $eventType->value,
            'severity' => $severity,
            'description' => $description,
            'event_data' => $eventData,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Store in security events table
        try {
            $device = $deviceId !== null ? $this->deviceRepository->findById($deviceId) : null;
            $tenantId = $device?->tenantId ?? 0;

            $securityEvent = \Modules\Restaurant\Domain\Entities\IoTSecurityEvent::create(
                iotDeviceId: $deviceId,
                tenantId: $tenantId,
                eventType: $eventType,
                description: $description,
                eventData: $eventData,
                sourceIp: $sourceIp,
                fingerprint: $deviceIdentifier,
                correlationId: $correlationId,
            );

            $this->securityEventRepository->save($securityEvent);
        } catch (Exception $e) {
            Log::error('Failed to save security event to database', [
                'error' => $e->getMessage(),
                'device_id' => $deviceId,
                'event_type' => $eventType->value,
            ]);
        }
    }

    /**
     * Validate nonce to prevent replay attacks
     */
    private function validateNonce(int $deviceId, string $nonce): bool
    {
        $key = "iot_nonce:{$deviceId}:{$nonce}";
        
        // Check if nonce already exists (replay attack)
        $exists = Redis::exists($key);
        if ($exists) {
            return false;
        }
        
        // Store nonce with TTL
        Redis::setex($key, self::NONCE_TTL, '1');
        return true;
    }

    /**
     * Detect anomalies in device behavior
     */
    public function detectAnomaly(int $deviceId, array $telemetryData): ?string
    {
        $cacheKey = "iot_anomaly:{$deviceId}";
        
        // Get recent telemetry for comparison
        $recentTelemetry = Cache::get($cacheKey, []);
        
        if (empty($recentTelemetry)) {
            Cache::put($cacheKey, [$telemetryData], self::CACHE_TTL);
            return null;
        }

        // Simple anomaly detection: check for sudden spikes
        foreach ($telemetryData as $metric => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $avg = collect($recentTelemetry)->avg(function ($item) use ($metric) {
                return $item[$metric] ?? null;
            });

            if ($avg !== null) {
                $deviation = abs(($value - $avg) / $avg);
                
                // If deviation > 50%, flag as anomaly
                if ($deviation > 0.5) {
                    $deviationPercent = $deviation * 100;
                    $anomalyMessage = "Anomaly detected in metric {$metric}: value {$value} deviates {$deviationPercent}% from average {$avg}";
                    
                    $this->logSecurityEvent(
                        $deviceId,
                        "device_{$deviceId}",
                        IoTSecurityEventType::ANOMALY_DETECTED,
                        $anomalyMessage,
                        ['metric' => $metric, 'value' => $value, 'average' => $avg, 'deviation' => $deviation],
                    );

                    // Update recent telemetry
                    $recentTelemetry[] = $telemetryData;
                    if (count($recentTelemetry) > 10) {
                        array_shift($recentTelemetry);
                    }
                    Cache::put($cacheKey, $recentTelemetry, self::CACHE_TTL);

                    return $anomalyMessage;
                }
            }
        }

        // Update recent telemetry
        $recentTelemetry[] = $telemetryData;
        if (count($recentTelemetry) > 10) {
            array_shift($recentTelemetry);
        }
        Cache::put($cacheKey, $recentTelemetry, self::CACHE_TTL);

        return null;
    }
}
