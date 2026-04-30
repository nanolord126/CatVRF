<?php

declare(strict_types=1);

namespace App\DTO\Security;

use App\Enums\VpnRiskLevel;
use Illuminate\Http\Request;

/**
 * VPN Detection Result DTO
 *
 * Immutable data transfer object for VPN detection results.
 * Contains all information about detected VPN/proxy usage and risk assessment.
 */
final readonly class VpnDetectionResult
{
    public function __construct(
        public bool $isVpn,
        public ?string $provider,
        public VpnRiskLevel $riskLevel,
        public ?string $ipAddress,
        public ?string $country,
        public ?string $city,
        public ?string $asn,
        public ?string $isp,
        public bool $isTor,
        public bool $isDatacenter,
        public bool $isResidentialProxy,
        public bool $isCorporateVpn,
        public bool $hasBehavioralAnomaly,
        public array $detectionSources,
        public array $metadata,
    ) {}

    /**
     * Create a clean result (no VPN detected)
     */
    public static function clean(Request $request): self
    {
        return new self(
            isVpn: false,
            provider: null,
            riskLevel: VpnRiskLevel::LOW,
            ipAddress: $request->ip(),
            country: null,
            city: null,
            asn: null,
            isp: null,
            isTor: false,
            isDatacenter: false,
            isResidentialProxy: false,
            isCorporateVpn: false,
            hasBehavioralAnomaly: false,
            detectionSources: [],
            metadata: [],
        );
    }

    /**
     * Create a VPN detected result
     */
    public static function detected(
        Request $request,
        string $provider,
        VpnRiskLevel $riskLevel,
        array $detectionDetails = []
    ): self {
        return new self(
            isVpn: true,
            provider: $provider,
            riskLevel: $riskLevel,
            ipAddress: $request->ip(),
            country: $detectionDetails['country'] ?? null,
            city: $detectionDetails['city'] ?? null,
            asn: $detectionDetails['asn'] ?? null,
            isp: $detectionDetails['isp'] ?? null,
            isTor: $detectionDetails['is_tor'] ?? false,
            isDatacenter: $detectionDetails['is_datacenter'] ?? false,
            isResidentialProxy: $detectionDetails['is_residential_proxy'] ?? false,
            isCorporateVpn: $detectionDetails['is_corporate_vpn'] ?? false,
            hasBehavioralAnomaly: $detectionDetails['has_behavioral_anomaly'] ?? false,
            detectionSources: $detectionDetails['sources'] ?? [],
            metadata: $detectionDetails['metadata'] ?? [],
        );
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'is_vpn' => $this->isVpn,
            'vpn_provider' => $this->provider,
            'vpn_risk_level' => $this->riskLevel->value,
            'ip_address' => $this->ipAddress,
            'country' => $this->country,
            'city' => $this->city,
            'asn' => $this->asn,
            'isp' => $this->isp,
            'is_tor' => $this->isTor,
            'is_datacenter' => $this->isDatacenter,
            'is_residential_proxy' => $this->isResidentialProxy,
            'is_corporate_vpn' => $this->isCorporateVpn,
            'has_behavioral_anomaly' => $this->hasBehavioralAnomaly,
            'detection_sources' => $this->detectionSources,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create from array (for database retrieval)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isVpn: $data['is_vpn'] ?? false,
            provider: $data['vpn_provider'] ?? null,
            riskLevel: VpnRiskLevel::from($data['vpn_risk_level'] ?? VpnRiskLevel::LOW->value),
            ipAddress: $data['ip_address'] ?? null,
            country: $data['country'] ?? null,
            city: $data['city'] ?? null,
            asn: $data['asn'] ?? null,
            isp: $data['isp'] ?? null,
            isTor: $data['is_tor'] ?? false,
            isDatacenter: $data['is_datacenter'] ?? false,
            isResidentialProxy: $data['is_residential_proxy'] ?? false,
            isCorporateVpn: $data['is_corporate_vpn'] ?? false,
            hasBehavioralAnomaly: $data['has_behavioral_anomaly'] ?? false,
            detectionSources: $data['detection_sources'] ?? [],
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Check if protection measures should be applied
     */
    public function requiresProtection(): bool
    {
        return $this->riskLevel !== VpnRiskLevel::LOW;
    }

    /**
     * Get summary for logging
     */
    public function getSummary(): string
    {
        if (!$this->isVpn) {
            return 'No VPN detected';
        }

        return sprintf(
            'VPN detected: %s, Provider: %s, Risk: %s, Sources: %s',
            $this->ipAddress,
            $this->provider ?? 'Unknown',
            $this->riskLevel->value,
            implode(', ', $this->detectionSources)
        );
    }
}
