<?php

declare(strict_types=1);

namespace App\DTO\Security;

use App\Enums\ProxyType;
use App\Enums\ProxyProvider;
use App\Enums\ResidentialProxyRiskLevel;
use Illuminate\Http\Request;

/**
 * Residential Proxy Detection Result DTO
 *
 * Immutable data transfer object for residential proxy detection results.
 * Contains all information about detected residential proxy usage and risk assessment.
 */
final readonly class ResidentialProxyDetectionResult
{
    public function __construct(
        public bool $isResidentialProxy,
        public ProxyType $proxyType,
        public ProxyProvider $proxyProvider,
        public ResidentialProxyRiskLevel $riskLevel,
        public int $confidenceScore,
        public string $ipAddress,
        public ?string $countryCode,
        public ?string $city,
        public bool $isRotating,
        public bool $isEthical,
        public bool $isIspBacked,
        public bool $hasBehavioralAnomaly,
        public bool $geoMismatch,
        public bool $isRussianTerritoryViolation,
        public array $detectionSources,
        public array $metadata,
        public bool $isWhitelisted = false,
        public bool $isBlacklisted = false,
    ) {}

    /**
     * Create a clean result (no residential proxy detected)
     */
    public static function clean(Request $request): self
    {
        return new self(
            isResidentialProxy: false,
            proxyType: ProxyType::NONE,
            proxyProvider: ProxyProvider::UNKNOWN,
            riskLevel: ResidentialProxyRiskLevel::LOW,
            confidenceScore: 0,
            ipAddress: $request->ip(),
            countryCode: null,
            city: null,
            isRotating: false,
            isEthical: false,
            isIspBacked: false,
            hasBehavioralAnomaly: false,
            geoMismatch: false,
            isRussianTerritoryViolation: false,
            detectionSources: [],
            metadata: [],
        );
    }

    /**
     * Create a whitelisted result
     */
    public static function whitelisted(Request $request): self
    {
        return new self(
            isResidentialProxy: false,
            proxyType: ProxyType::CORPORATE_VPN,
            proxyProvider: ProxyProvider::UNKNOWN,
            riskLevel: ResidentialProxyRiskLevel::LOW,
            confidenceScore: 0,
            ipAddress: $request->ip(),
            countryCode: null,
            city: null,
            isRotating: false,
            isEthical: false,
            isIspBacked: false,
            hasBehavioralAnomaly: false,
            geoMismatch: false,
            isRussianTerritoryViolation: false,
            detectionSources: ['whitelist'],
            metadata: [],
            isWhitelisted: true,
        );
    }

    /**
     * Create a blacklisted result
     */
    public static function blacklisted(Request $request): self
    {
        return new self(
            isResidentialProxy: true,
            proxyType: ProxyType::UNKNOWN,
            proxyProvider: ProxyProvider::UNKNOWN,
            riskLevel: ResidentialProxyRiskLevel::PERMANENT_BLOCK,
            confidenceScore: 100,
            ipAddress: $request->ip(),
            countryCode: null,
            city: null,
            isRotating: false,
            isEthical: false,
            isIspBacked: false,
            hasBehavioralAnomaly: false,
            geoMismatch: false,
            isRussianTerritoryViolation: false,
            detectionSources: ['blacklist'],
            metadata: [],
            isBlacklisted: true,
        );
    }

    /**
     * Create a residential proxy detected result
     */
    public static function detected(
        Request $request,
        ProxyType $proxyType,
        ProxyProvider $proxyProvider,
        ResidentialProxyRiskLevel $riskLevel,
        int $confidenceScore,
        array $detectionDetails = []
    ): self {
        return new self(
            isResidentialProxy: true,
            proxyType: $proxyType,
            proxyProvider: $proxyProvider,
            riskLevel: $riskLevel,
            confidenceScore: $confidenceScore,
            ipAddress: $request->ip(),
            countryCode: $detectionDetails['country_code'] ?? null,
            city: $detectionDetails['city'] ?? null,
            isRotating: $detectionDetails['is_rotating'] ?? false,
            isEthical: $detectionDetails['is_ethical'] ?? false,
            isIspBacked: $detectionDetails['is_isp_backed'] ?? false,
            hasBehavioralAnomaly: $detectionDetails['has_behavioral_anomaly'] ?? false,
            geoMismatch: $detectionDetails['geo_mismatch'] ?? false,
            isRussianTerritoryViolation: $detectionDetails['is_russian_territory_violation'] ?? false,
            detectionSources: $detectionDetails['detection_sources'] ?? [],
            metadata: $detectionDetails['metadata'] ?? [],
        );
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'is_residential_proxy' => $this->isResidentialProxy,
            'proxy_type' => $this->proxyType->value,
            'proxy_provider' => $this->proxyProvider->value,
            'risk_level' => $this->riskLevel->value,
            'confidence_score' => $this->confidenceScore,
            'ip_address' => $this->ipAddress,
            'country_code' => $this->countryCode,
            'city' => $this->city,
            'is_rotating' => $this->isRotating,
            'is_ethical' => $this->isEthical,
            'is_isp_backed' => $this->isIspBacked,
            'has_behavioral_anomaly' => $this->hasBehavioralAnomaly,
            'geo_mismatch' => $this->geoMismatch,
            'is_russian_territory_violation' => $this->isRussianTerritoryViolation,
            'detection_sources' => $this->detectionSources,
            'metadata' => $this->metadata,
            'is_whitelisted' => $this->isWhitelisted,
            'is_blacklisted' => $this->isBlacklisted,
        ];
    }

    /**
     * Create from array (for database retrieval)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isResidentialProxy: $data['is_residential_proxy'] ?? false,
            proxyType: ProxyType::from($data['proxy_type'] ?? ProxyType::NONE->value),
            proxyProvider: ProxyProvider::from($data['proxy_provider'] ?? ProxyProvider::UNKNOWN->value),
            riskLevel: ResidentialProxyRiskLevel::from($data['risk_level'] ?? ResidentialProxyRiskLevel::LOW->value),
            confidenceScore: $data['confidence_score'] ?? 0,
            ipAddress: $data['ip_address'] ?? null,
            countryCode: $data['country_code'] ?? null,
            city: $data['city'] ?? null,
            isRotating: $data['is_rotating'] ?? false,
            isEthical: $data['is_ethical'] ?? false,
            isIspBacked: $data['is_isp_backed'] ?? false,
            hasBehavioralAnomaly: $data['has_behavioral_anomaly'] ?? false,
            geoMismatch: $data['geo_mismatch'] ?? false,
            isRussianTerritoryViolation: $data['is_russian_territory_violation'] ?? false,
            detectionSources: $data['detection_sources'] ?? [],
            metadata: $data['metadata'] ?? [],
            isWhitelisted: $data['is_whitelisted'] ?? false,
            isBlacklisted: $data['is_blacklisted'] ?? false,
        );
    }

    /**
     * Check if protection measures should be applied
     */
    public function requiresProtection(): bool
    {
        return $this->riskLevel !== ResidentialProxyRiskLevel::LOW && !$this->isWhitelisted;
    }

    /**
     * Get summary for logging
     */
    public function getSummary(): string
    {
        if (!$this->isResidentialProxy) {
            return 'No residential proxy detected';
        }

        if ($this->isWhitelisted) {
            return sprintf('IP whitelisted: %s', $this->ipAddress);
        }

        if ($this->isBlacklisted) {
            return sprintf('IP blacklisted: %s', $this->ipAddress);
        }

        return sprintf(
            'Residential proxy detected: %s, Type: %s, Provider: %s, Risk: %s, Confidence: %d%%, Sources: %s',
            $this->ipAddress,
            $this->proxyType->value,
            $this->proxyProvider->value,
            $this->riskLevel->value,
            $this->confidenceScore,
            implode(', ', $this->detectionSources)
        );
    }

    /**
     * Get protection measures for this risk level
     */
    public function getProtectionMeasures(): array
    {
        return $this->riskLevel->getProtectionMeasures();
    }
}
