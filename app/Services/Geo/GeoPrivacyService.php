<?php declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Log\LogManager;

/**
 * Geolocation Privacy Service
 * 
 * Handles 152-ФЗ compliance for geolocation data:
 * - Coordinate anonymization
 * - Consent management
 * - Data minimization
 * - Geohash-based privacy zones
 */
final readonly class GeoPrivacyService
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}

    /**
     * Anonymize coordinates for medical data (152-ФЗ compliance)
     * Lower precision for medical records
     */
    public function anonymizeMedicalCoordinates(float $lat, float $lon): array
    {
        $precision = $this->config->get('geo.privacy.medical_data_precision', 3);
        
        return [
            'lat' => round($lat, $precision),
            'lon' => round($lon, $precision),
            'precision' => $precision,
            'anonymized_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Anonymize coordinates for general use
     */
    public function anonymizeCoordinates(float $lat, float $lon, ?string $context = null): array
    {
        $precision = $this->config->get('geo.privacy.anonymization_precision', 4);

        // Medical context requires lower precision
        if ($this->isMedicalContext($context)) {
            $precision = $this->config->get('geo.privacy.medical_data_precision', 3);
        }

        return [
            'lat' => round($lat, $precision),
            'lon' => round($lon, $precision),
            'precision' => $precision,
            'context' => $context,
            'anonymized_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get privacy-preserving geohash
     */
    public function getPrivacyGeohash(float $lat, float $lon, ?string $context = null): string
    {
        $precision = $this->config->get('geo.privacy.geohash_precision', 7);

        // Lower precision for medical data
        if ($this->isMedicalContext($context)) {
            $precision = max(5, $precision - 2); // Reduce precision by 2
        }

        return $this->calculateGeohash($lat, $lon, $precision);
    }

    /**
     * Check if context is medical-related
     */
    private function isMedicalContext(?string $context): bool
    {
        if (!$context) {
            return false;
        }

        $medicalContexts = [
            'medical', 'healthcare', 'doctor', 'clinic', 'hospital',
            'patient', 'diagnosis', 'treatment', 'appointment',
            'home_visit', 'emergency', 'ambulance',
        ];

        foreach ($medicalContexts as $keyword) {
            if (stripos($context, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate geohash (simplified implementation)
     * For production, use dedicated library like 'geohash-php'
     */
    private function calculateGeohash(float $lat, float $lon, int $precision): string
    {
        $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
        $latRange = [-90.0, 90.0];
        $lonRange = [-180.0, 180.0];
        $geohash = '';
        $bits = 0;
        $bit = 0;
        $evenBit = true;

        while (strlen($geohash) < $precision) {
            if ($evenBit) {
                $mid = ($lonRange[0] + $lonRange[1]) / 2;
                if ($lon > $mid) {
                    $bit |= 1;
                    $lonRange[0] = $mid;
                } else {
                    $lonRange[1] = $mid;
                }
            } else {
                $mid = ($latRange[0] + $latRange[1]) / 2;
                if ($lat > $mid) {
                    $bit |= 1;
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }

            $evenBit = !$evenBit;

            if ($bits < 4) {
                $bits++;
            } else {
                $geohash .= $base32[$bit];
                $bits = 0;
                $bit = 0;
            }
        }

        return $geohash;
    }

    /**
     * Validate if coordinates are properly anonymized
     */
    public function validateAnonymization(array $coordinates, int $expectedPrecision): bool
    {
        $lat = $coordinates['lat'] ?? 0;
        $lon = $coordinates['lon'] ?? 0;
        
        $latString = (string) $lat;
        $lonString = (string) $lon;
        
        $latDecimals = strpos($latString, '.') !== false 
            ? strlen(substr($latString, strpos($latString, '.') + 1)) 
            : 0;
        
        $lonDecimals = strpos($lonString, '.') !== false 
            ? strlen(substr($lonString, strpos($lonString, '.') + 1)) 
            : 0;

        return $latDecimals <= $expectedPrecision && $lonDecimals <= $expectedPrecision;
    }

    /**
     * Get privacy zone radius based on geohash precision
     */
    public function getPrivacyZoneRadius(int $geohashPrecision): float
    {
        // Approximate radius in kilometers
        $radii = [
            1 => 2500,
            2 => 630,
            3 => 78,
            4 => 20,
            5 => 2.4,
            6 => 0.61,
            7 => 0.076,
            8 => 0.019,
        ];

        return $radii[$geohashPrecision] ?? 0.019;
    }

    /**
     * Log privacy event for audit trail
     */
    public function logPrivacyEvent(
        string $eventType,
        int $userId,
        array $originalCoords,
        array $anonymizedCoords,
        ?string $context = null,
    ): void {
        $this->logger->channel('audit')->info('Geolocation privacy event', [
            'event_type' => $eventType,
            'user_id' => $userId,
            'context' => $context,
            'original_lat' => $originalCoords['lat'] ?? null,
            'original_lon' => $originalCoords['lon'] ?? null,
            'anonymized_lat' => $anonymizedCoords['lat'] ?? null,
            'anonymized_lon' => $anonymizedCoords['lon'] ?? null,
            'precision' => $anonymizedCoords['precision'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Check if user has consent for geolocation tracking
     */
    public function hasConsent(int $userId, string $consentType = 'tracking'): bool
    {
        // Check database for user consent
        $consent = \Illuminate\Support\Facades\DB::table('user_consents')
            ->where('user_id', $userId)
            ->where('consent_type', $consentType)
            ->where('granted', true)
            ->where('expires_at', '>', now())
            ->first();

        return (bool) $consent;
    }

    /**
     * Record user consent for geolocation
     */
    public function recordConsent(int $userId, string $consentType = 'tracking', bool $granted = true): void
    {
        \Illuminate\Support\Facades\DB::table('user_consents')->insert([
            'user_id' => $userId,
            'consent_type' => $consentType,
            'granted' => $granted,
            'granted_at' => now(),
            'expires_at' => now()->addYears(1),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logger->channel('audit')->info('Geolocation consent recorded', [
            'user_id' => $userId,
            'consent_type' => $consentType,
            'granted' => $granted,
        ]);
    }
}
