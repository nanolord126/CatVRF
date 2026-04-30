<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\BehavioralDataPoint;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * Behavioral Biometrics Collector Service
 *
 * Collects and validates behavioral signals from frontend.
 * Handles batching, throttling, and temporary storage before analysis.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class BehavioralBiometricsCollectorService
{
    public function __construct(
        private readonly LogManager $log,
    ) {}

    /**
     * Collect behavioral signals from frontend
     *
     * @param  Request  $request  HTTP request with signal data
     * @return array Collection result
     */
    public function collect(Request $request): array
    {
        $startTime = microtime(true);
        $correlationId = Str::uuid()->toString();

        try {
            $user = $request->user();
            if (! $user) {
                return [
                    'success' => false,
                    'error' => 'Unauthenticated',
                    'correlation_id' => $correlationId,
                ];
            }

            // Check consent
            if (! $this->hasConsent($user)) {
                return [
                    'success' => false,
                    'error' => 'Consent not granted',
                    'correlation_id' => $correlationId,
                ];
            }

            // Validate signal data
            $signals = $request->input('signals', []);
            if (empty($signals)) {
                return [
                    'success' => false,
                    'error' => 'No signals provided',
                    'correlation_id' => $correlationId,
                ];
            }

            // Store data points
            $sessionId = session()->getId();
            $action = $request->input('action', 'unknown');
            
            $this->storeDataPoint(
                user: $user,
                sessionId: $sessionId,
                action: $action,
                signals: $signals,
                request: $request,
            );

            $latencyMs = (microtime(true) - $startTime) * 1000;

            $this->log->debug('Behavioral signals collected', [
                'user_id' => $user->id,
                'session_id' => $sessionId,
                'action' => $action,
                'signal_types' => array_keys($signals),
                'correlation_id' => $correlationId,
                'latency_ms' => round($latencyMs, 2),
            ]);

            return [
                'success' => true,
                'correlation_id' => $correlationId,
                'latency_ms' => round($latencyMs, 2),
            ];
        } catch (\Throwable $e) {
            $this->log->error('Behavioral signal collection failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => 'Collection failed',
                'correlation_id' => $correlationId,
            ];
        }
    }

    /**
     * Check if user has consented to behavioral data collection
     */
    private function hasConsent(User $user): bool
    {
        // Check explicit consent setting
        if (isset($user->behavioral_consent)) {
            return (bool) $user->behavioral_consent;
        }

        // Fall back to default config
        return config('behavioral.consent.default_enabled', true);
    }

    /**
     * Store behavioral data point
     */
    private function storeDataPoint(
        User $user,
        string $sessionId,
        string $action,
        array $signals,
        Request $request
    ): void {
        $retentionDays = config('behavioral.retention.raw_data_points_days', 30);

        BehavioralDataPoint::create([
            'tenant_id' => $user->tenant_id,
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'action' => $action,
            'typing_pattern' => $signals['typing'] ?? null,
            'mouse_dynamics' => $signals['mouse'] ?? null,
            'touch_gestures' => $signals['touch'] ?? null,
            'ip_address' => $this->maskIpAddress($request->ip()),
            'user_agent' => $request->userAgent(),
            'device_fingerprint' => $this->hashDeviceFingerprint($request->header('Device-Fingerprint')),
            'collected_at' => CarbonImmutable::now(),
            'expires_at' => CarbonImmutable::now()->addDays($retentionDays),
        ]);
    }

    /**
     * Mask IP address for privacy (last octet)
     */
    private function maskIpAddress(?string $ip): ?string
    {
        if (! $ip || ! config('behavioral.privacy.mask_ip_addresses', true)) {
            return $ip;
        }

        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }

        // For IPv6, just return null for now
        return null;
    }

    /**
     * Hash device fingerprint for privacy
     */
    private function hashDeviceFingerprint(?string $fingerprint): ?string
    {
        if (! $fingerprint || ! config('behavioral.privacy.hash_device_fingerprints', true)) {
            return $fingerprint;
        }

        return hash('sha256', $fingerprint);
    }
}
