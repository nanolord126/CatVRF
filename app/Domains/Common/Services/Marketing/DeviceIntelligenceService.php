<?php

namespace App\Domains\Common\Services\Marketing;

use App\Models\User;
use App\Domains\Common\Models\UserDeviceFingerprint;
use Illuminate\Support\{Carbon, Facades, Str};
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Throwable;

class DeviceIntelligenceService
{
    private string $correlationId;
    private ?string $tenantId;

    public function __construct()
    {
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Запись или обновление цифрового отпечатка устройства пользователя.
     */
    public function captureFingerprint(User $user, array $clientData): UserDeviceFingerprint
    {
        $this->correlationId = Str::uuid();

        try {
            Log::channel('security')->info('DeviceIntelligence: capturing fingerprint', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'device_type' => $this->getDeviceScale(),
            ]);

            $fingerprintHash = $this->generateUniqueHash($user, $clientData);

            $fingerprint = UserDeviceFingerprint::updateOrCreate(
                ['user_id' => $user->id, 'fingerprint_hash' => $fingerprintHash],
                [
                    'os_name' => $this->getOSFromUserAgent(Request::userAgent() ?? ''),
                    'os_version' => $this->getOSVersion(Request::userAgent() ?? ''),
                    'browser_name' => $this->getBrowserFromUserAgent(Request::userAgent() ?? ''),
                    'browser_version' => $this->getBrowserVersion(Request::userAgent() ?? ''),
                    'device_type' => $this->getDeviceScale(),
                    'device_model' => $this->getDeviceModel(Request::userAgent() ?? ''),
                    'screen_resolution' => [
                        'w' => $clientData['screen_width'] ?? null,
                        'h' => $clientData['screen_height'] ?? null,
                        'dpr' => $clientData['pixel_ratio'] ?? 1,
                    ],
                    'browser_features' => [
                        'lang' => Request::getPreferredLanguage(),
                        'tz' => $clientData['timezone'] ?? 'UTC',
                        'cookies' => !$this->isBotUserAgent(Request::userAgent() ?? ''),
                    ],
                    'device_memory' => $clientData['memory'] ?? null,
                    'hardware_concurrency' => $clientData['cores'] ?? null,
                    'last_seen_at' => Carbon::now(),
                    'ip_address' => Request::ip(),
                    'is_trusted' => $this->calculateTrustScore($clientData) > 70,
                    'risk_score' => 100 - $this->calculateTrustScore($clientData),
                    'correlation_id' => $this->correlationId,
                    'tenant_id' => $this->tenantId,
                    'metadata' => [
                        'connection_type' => $clientData['network'] ?? 'unknown',
                        'is_vpn' => Request::header('X-In-Proxy') ? true : false,
                        'is_robot' => $this->isBotUserAgent(Request::userAgent() ?? ''),
                        'user_agent' => Request::userAgent(),
                    ]
                ]
            );

            AuditLog::create([
                'entity_type' => UserDeviceFingerprint::class,
                'entity_id' => $fingerprint->id,
                'action' => $fingerprint->wasRecentlyCreated ? 'created' : 'updated',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'fingerprint_hash' => $fingerprintHash,
                    'device_type' => $fingerprint->device_type,
                    'os' => $fingerprint->os_name,
                    'browser' => $fingerprint->browser_name,
                    'risk_score' => $fingerprint->risk_score,
                ],
            ]);

            Log::channel('security')->info('DeviceIntelligence: fingerprint captured', [
                'correlation_id' => $this->correlationId,
                'fingerprint_id' => $fingerprint->id,
                'user_id' => $user->id,
                'risk_score' => $fingerprint->risk_score,
            ]);

            return $fingerprint;
        } catch (Throwable $e) {
            Log::error('DeviceIntelligence: fingerprint capture failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Определение маркетингового сегмента устройства для точной выдачи (Ad Serving).
     */
    public function getTargetingContext(User $user): array
    {
        try {
            $latest = UserDeviceFingerprint::where('user_id', $user->id)
                ->orderBy('last_seen_at', 'desc')
                ->first();

            if (!$latest) {
                return ['segment' => 'unknown', 'risk_score' => 50];
            }

            return [
                'is_high_end' => ($latest->device_memory >= 8 || $latest->os_name === 'OS X'),
                'screen_category' => $this->mapScreenToCategory($latest->screen_resolution),
                'os_family' => $latest->os_name,
                'is_mobile' => in_array($latest->device_type, ['mobile', 'tablet']),
                'connection_quality' => $latest->metadata['connection_type'] ?? 'average',
                'is_trusted' => $latest->is_trusted,
                'risk_score' => $latest->risk_score,
                'is_vpn' => $latest->metadata['is_vpn'] ?? false,
            ];
        } catch (Throwable $e) {
            Log::error('DeviceIntelligence: targeting context failed', [
                'correlation_id' => $this->correlationId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return ['segment' => 'unknown', 'risk_score' => 100];
        }
    }

    protected function calculateTrustScore(array $clientData): int
    {
        $score = 50; // Base score

        // Check if it's likely a bot
        $userAgent = Request::userAgent() ?? '';
        if (!$this->isBotUserAgent($userAgent)) $score += 15;
        
        if (!Request::header('X-In-Proxy')) $score += 15;
        if ($clientData['timezone'] ?? null) $score += 10;
        if ($clientData['screen_width'] ?? null) $score += 10;

        return min(100, $score);
    }

    protected function generateUniqueHash(User $user, array $data): string
    {
        $userAgent = Request::userAgent() ?? '';
        return hash('sha256', $user->id . $userAgent . ($data['screen_width'] ?? ''));
    }

    protected function getDeviceScale(): string
    {
        $userAgent = Request::userAgent() ?? '';
        
        // Simple UA parsing
        if (stripos($userAgent, 'tablet') !== false || stripos($userAgent, 'ipad') !== false) return 'tablet';
        if (stripos($userAgent, 'mobile') !== false || stripos($userAgent, 'android') !== false) return 'mobile';
        return 'desktop';
    }

    private function isBotUserAgent(string $userAgent): bool
    {
        $botPatterns = ['bot', 'crawl', 'spider', 'scrape', 'curl', 'wget'];
        $ua = strtolower($userAgent);
        foreach ($botPatterns as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function getOSFromUserAgent(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (stripos($ua, 'windows') !== false) return 'Windows';
        if (stripos($ua, 'mac') !== false) return 'macOS';
        if (stripos($ua, 'linux') !== false) return 'Linux';
        if (stripos($ua, 'android') !== false) return 'Android';
        if (stripos($ua, 'iphone') !== false || stripos($ua, 'ipad') !== false) return 'iOS';
        return 'Unknown';
    }

    private function getOSVersion(string $userAgent): string
    {
        preg_match('/(Windows NT|Mac OS X|Android|iPhone OS) ([\d._]+)/', $userAgent, $matches);
        return $matches[2] ?? 'Unknown';
    }

    private function getBrowserFromUserAgent(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if (stripos($ua, 'edge') !== false) return 'Edge';
        if (stripos($ua, 'chrome') !== false) return 'Chrome';
        if (stripos($ua, 'safari') !== false) return 'Safari';
        if (stripos($ua, 'firefox') !== false) return 'Firefox';
        if (stripos($ua, 'opera') !== false) return 'Opera';
        return 'Unknown';
    }

    private function getBrowserVersion(string $userAgent): string
    {
        preg_match('/(Chrome|Safari|Firefox|Edge|Opera)[\/\s]([0-9.]+)/', $userAgent, $matches);
        return $matches[2] ?? 'Unknown';
    }

    private function getDeviceModel(string $userAgent): string
    {
        preg_match('/(?:SM-|GT-|Nexus|Pixel|iPhone|iPad)[\w\s]+/i', $userAgent, $matches);
        return $matches[0] ?? 'Generic';
    }

    protected function mapScreenToCategory(?array $res): string
    {
        if (!$res || !isset($res['w'])) return 'standard';
        $w = $res['w'];
        if ($w >= 2560) return 'ultra_wide';
        if ($w >= 1920) return 'full_hd';
        if ($w <= 480) return 'compact_mobile';
        return 'standard';
    }
}
