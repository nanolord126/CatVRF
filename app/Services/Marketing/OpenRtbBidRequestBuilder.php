<?php declare(strict_types=1);

namespace App\Services\Marketing;

use App\Services\ML\AnonymizationService;
use App\Services\ML\UserTasteAnalyzerService;
use Illuminate\Support\Str;

/**
 * OpenRtbBidRequestBuilder - Builds OpenRTB 2.6 BidRequest
 * 
 * Constructs compliant OpenRTB 2.6 BidRequest with CatVRF extensions
 */
final readonly class OpenRtbBidRequestBuilder
{
    public function __construct(
        private readonly AnonymizationService $anonymizer,
        private readonly UserTasteAnalyzerService $tasteAnalyzer,
    ) {}

    /**
     * Build OpenRTB 2.6 BidRequest
     * 
     * @param array $context Request context
     * @param string $correlationId
     * @return array
     */
    public function build(array $context, string $correlationId): array
    {
        $userId = $context['user_id'] ?? 0;
        $vertical = $context['vertical'] ?? 'general';
        $placement = $context['placement'] ?? 'banner';

        $bidRequest = [
            'id' => Str::uuid()->toString(),
            'imp' => [$this->buildImpression($context)],
            'site' => $this->buildSite($context),
            'device' => $this->buildDevice($context),
            'user' => $this->buildUser($userId, $context),
            'regs' => $this->buildRegs(),
            'ext' => $this->buildCatVrfExtension($userId, $vertical, $placement, $context),
            'at' => 1, // First price auction (can be overridden)
            'tmax' => config('monetization.rtb.timeout_ms', 120),
        ];

        return $bidRequest;
    }

    /**
     * Build Impression object
     */
    private function buildImpression(array $context): array
    {
        $impId = Str::uuid()->toString();
        $placement = $context['placement'] ?? 'banner';

        $imp = [
            'id' => $impId,
            'banner' => $this->buildBanner($context),
            'bidfloor' => $context['floor_cpm_kopecks'] ?? config('monetization.rtb.default_floor_cpm_kopecks', 500),
            'bidfloorcur' => 'RUB',
        ];

        // Add video for video placements
        if ($placement === 'video' || $placement === 'short_video') {
            $imp['video'] = $this->buildVideo($context);
        }

        return $imp;
    }

    /**
     * Build Banner object
     */
    private function buildBanner(array $context): array
    {
        return [
            'w' => $context['width'] ?? 300,
            'h' => $context['height'] ?? 250,
            'pos' => 0, // Unknown
            'btype' => [], // Banner types
            'battr' => [], // Banner attributes
        ];
    }

    /**
     * Build Video object
     */
    private function buildVideo(array $context): array
    {
        return [
            'mimes' => ['video/mp4', 'video/webm'],
            'minduration' => 5,
            'maxduration' => 60,
            'protocols' => [2, 3], // HTTP, HTTPS
            'w' => $context['width'] ?? 640,
            'h' => $context['height'] ?? 480,
            'placement' => 1, // Mid-roll
        ];
    }

    /**
     * Build Site object
     */
    private function buildSite(array $context): array
    {
        return [
            'id' => (string)($context['tenant_id'] ?? 0),
            'name' => $context['site_name'] ?? 'CatVRF',
            'domain' => $context['domain'] ?? 'catvrf.ru',
            'cat' => [], // IAB categories
            'sectioncat' => [],
            'pagecat' => [],
            'publisher' => [
                'id' => (string)($context['tenant_id'] ?? 0),
            ],
        ];
    }

    /**
     * Build Device object
     */
    private function buildDevice(array $context): array
    {
        return [
            'ua' => $context['user_agent'] ?? request()->userAgent() ?? '',
            'ip' => $this->anonymizeIp($context['ip'] ?? request()->ip()),
            'geo' => $this->buildGeo($context),
            'devicetype' => $this->getDeviceType($context),
            'os' => $this->getOS($context),
            'make' => '',
            'model' => '',
            'connectiontype' => $this->getConnectionType($context),
        ];
    }

    /**
     * Build User object
     */
    private function buildUser(int $userId, array $context): array
    {
        $user = [
            'id' => $userId > 0 ? $this->anonymizer->anonymizeUserId($userId) : '',
            'buyeruid' => '',
            'yob' => null, // Year of birth (deprecated in 2.6)
            'gender' => '',
            'keywords' => '',
            'customdata' => '',
            'geo' => $this->buildGeo($context),
            'data' => [],
        ];

        // Add extended identifiers if available
        if ($userId > 0) {
            $user['eid'] = [
                [
                    'source' => 'catvrf.com',
                    'uids' => [
                        [
                            'id' => $this->anonymizer->anonymizeUserId($userId),
                            'atype' => 1, // Cookie-based
                        ],
                    ],
                ],
            ];
        }

        return $user;
    }

    /**
     * Build Regulations object
     */
    private function buildRegs(): array
    {
        return [
            'coppa' => 0,
            'gdpr' => 0,
            'us_privacy' => '',
            'ext' => [],
        ];
    }

    /**
     * Build CatVRF-specific extension
     */
    private function buildCatVrfExtension(int $userId, string $vertical, string $placement, array $context): array
    {
        $extension = [
            'catvrf' => [
                'vertical' => $vertical,
                'placement_type' => $placement,
                'tenant_id' => $context['tenant_id'] ?? 0,
            ],
        ];

        // Add anonymized user taste profile if available
        if ($userId > 0) {
            try {
                $tasteProfile = $this->tasteAnalyzer->getProfile($userId);
                $extension['catvrf']['anonymized_user_taste'] = $this->anonymizer->anonymizeUserId($userId);
            } catch (\Throwable $e) {
                // Fail gracefully if taste profile unavailable
            }
        }

        return $extension;
    }

    /**
     * Build Geo object
     */
    private function buildGeo(array $context): array
    {
        return [
            'lat' => $context['latitude'] ?? 0,
            'lon' => $context['longitude'] ?? 0,
            'country' => 'RUS',
            'region' => '',
            'city' => '',
            'type' => 1, // IP location
        ];
    }

    /**
     * Anonymize IP address (keep first 3 octets)
     */
    private function anonymizeIp(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0';
        }
        return '0.0.0.0';
    }

    /**
     * Get device type
     */
    private function getDeviceType(array $context): int
    {
        $ua = $context['user_agent'] ?? '';
        if (preg_match('/mobile|android|iphone/i', $ua)) {
            return 1; // Mobile
        } elseif (preg_match('/tablet|ipad/i', $ua)) {
            return 2; // Tablet
        }
        return 5; // Personal computer
    }

    /**
     * Get OS
     */
    private function getOS(array $context): int
    {
        $ua = $context['user_agent'] ?? '';
        if (preg_match('/windows/i', $ua)) {
            return 1; // Windows
        } elseif (preg_match('/mac|os x/i', $ua)) {
            return 2; // Mac
        } elseif (preg_match('/linux/i', $ua)) {
            return 3; // Linux
        } elseif (preg_match('/ios/i', $ua)) {
            return 4; // iOS
        } elseif (preg_match('/android/i', $ua)) {
            return 5; // Android
        }
        return 0; // Other
    }

    /**
     * Get connection type
     */
    private function getConnectionType(array $context): int
    {
        // Default to unknown
        return 0;
    }
}
