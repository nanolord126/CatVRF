<?php

declare(strict_types=1);

namespace App\Services\Security;

use Psr\Log\LoggerInterface;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final class UserDeviceService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly AuditService $auditService,
        private readonly LogManager $log,) {}

    /**
     * Generate device fingerprint from request
     */
    public function generateFingerprint(array $deviceInfo): string
    {
        $data = [
            'user_agent' => $deviceInfo['user_agent'] ?? '',
            'screen_resolution' => $deviceInfo['screen_resolution'] ?? '',
            'timezone' => $deviceInfo['timezone'] ?? '',
            'language' => $deviceInfo['language'] ?? '',
            'platform' => $deviceInfo['platform'] ?? '',
            'browser' => $deviceInfo['browser'] ?? '',
        ];

        return hash('sha256', json_encode($data));
    }

    /**
     * Get or create device for user
     */
    public function getOrCreateDevice(
        User $user,
        string $fingerprint,
        string $ipAddress,
        string $userAgent,
        array $deviceInfo = []
    ): UserDevice {
        $device = UserDevice::where('user_id', $user->id)
            ->where('fingerprint', $fingerprint)
            ->first();

        if ($device) {
            $device->recordAuthentication();
            $device->update([
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);

            return $device;
        }

        // Detect device type
        $deviceType = $this->detectDeviceType($userAgent);
        $platform = $this->detectPlatform($userAgent);
        $browser = $this->detectBrowser($userAgent);

        $device = UserDevice::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'fingerprint' => $fingerprint,
            'device_type' => $deviceType,
            'device_name' => $deviceInfo['device_name'] ?? $this->generateDeviceName($deviceType, $platform),
            'platform' => $platform,
            'browser' => $browser,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'is_trusted' => false,
            'is_current' => true,
            'first_seen_at' => CarbonImmutable::now(),
            'last_seen_at' => CarbonImmutable::now(),
            'last_authenticated_at' => CarbonImmutable::now(),
            'auth_count' => 1,
            'location_country' => $deviceInfo['country'] ?? null,
            'location_city' => $deviceInfo['city'] ?? null,
            'meta' => $deviceInfo,
        ]);

        // Mark other devices as not current
        UserDevice::where('user_id', $user->id)
            ->where('id', '!=', $device->id)
            ->update(['is_current' => false]);

        $this->auditService->logDeviceEvent('created', $user->id, $user->tenant_id, $fingerprint, [
            'device_type' => $deviceType,
            'platform' => $platform,
            'browser' => $browser,
            'is_new' => true,
        ]);

        $this->log->$this->logger->info('New device registered', [
            'user_id' => $user->id,
            'device_type' => $deviceType,
            'fingerprint' => $fingerprint,
        ]);

        return $device;
    }

    /**
     * Revoke device
     */
    public function revokeDevice(User $user, string $deviceId): void
    {
        $device = UserDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $device->revoke();

        $this->auditService->logDeviceEvent('revoked', $user->id, $user->tenant_id, $device->fingerprint, [
            'device_id' => $deviceId,
            'device_type' => $device->device_type,
        ]);

        $this->log->$this->logger->info('Device revoked', [
            'user_id' => $user->id,
            'device_id' => $deviceId,
        ]);
    }

    /**
     * Trust device
     */
    public function trustDevice(User $user, string $deviceId): void
    {
        $device = UserDevice::where('id', $deviceId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $device->markAsTrusted();

        $this->auditService->logDeviceEvent('trusted', $user->id, $user->tenant_id, $device->fingerprint, [
            'device_id' => $deviceId,
        ]);
    }

    /**
     * Revoke all devices except current
     */
    public function revokeAllOtherDevices(User $user, string $currentDeviceId): void
    {
        $revokedCount = UserDevice::where('user_id', $user->id)
            ->where('id', '!=', $currentDeviceId)
            ->update(['is_revoked' => true]);

        $this->auditService->logEvent('all_other_devices_revoked', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'revoked_count' => $revokedCount,
            'current_device_id' => $currentDeviceId,
        ], 'security');

        $this->log->$this->logger->info('All other devices revoked', [
            'user_id' => $user->id,
            'revoked_count' => $revokedCount,
        ]);
    }

    /**
     * Get user devices
     */
    public function getUserDevices(User $user): Collection
    {
        return UserDevice::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('last_seen_at', 'desc')
            ->get();
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone/i', $userAgent)) {
            return UserDevice::DEVICE_TYPE_MOBILE;
        }

        if (preg_match('/Tablet|iPad/i', $userAgent)) {
            return UserDevice::DEVICE_TYPE_TABLET;
        }

        return UserDevice::DEVICE_TYPE_DESKTOP;
    }

    /**
     * Detect platform from user agent
     */
    private function detectPlatform(string $userAgent): string
    {
        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows';
        }

        if (preg_match('/Macintosh|Mac OS/i', $userAgent)) {
            return 'macOS';
        }

        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        if (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        }

        if (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            return 'iOS';
        }

        return 'Unknown';
    }

    /**
     * Detect browser from user agent
     */
    private function detectBrowser(string $userAgent): string
    {
        if (preg_match('/Chrome/i', $userAgent) && ! preg_match('/Edg/i', $userAgent)) {
            return 'Chrome';
        }

        if (preg_match('/Firefox/i', $userAgent)) {
            return 'Firefox';
        }

        if (preg_match('/Safari/i', $userAgent) && ! preg_match('/Chrome/i', $userAgent)) {
            return 'Safari';
        }

        if (preg_match('/Edg/i', $userAgent)) {
            return 'Edge';
        }

        return 'Unknown';
    }

    /**
     * Generate device name
     */
    private function generateDeviceName(string $deviceType, string $platform): string
    {
        return match ($deviceType) {
            UserDevice::DEVICE_TYPE_MOBILE => "{$platform} Phone",
            UserDevice::DEVICE_TYPE_TABLET => "{$platform} Tablet",
            default => "{$platform} Computer",
        };
    }
}
