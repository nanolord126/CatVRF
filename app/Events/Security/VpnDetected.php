<?php

declare(strict_types=1);

namespace App\Events\Security;

use App\DTO\Security\VpnDetectionResult;
use App\Enums\VpnRiskLevel;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * VPN Detected Event
 *
 * Dispatched when VPN/proxy usage is detected during login or authentication.
 * Triggers notifications to user, tenant owners, and/or investors based on risk level.
 */
final readonly class VpnDetected
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public User $user,
        public VpnDetectionResult $detectionResult,
        public string $ipAddress,
        public ?string $userAgent,
        public ?string $correlationId,
    ) {}

    /**
     * Check if notification should be sent to user
     */
    public function shouldNotifyUser(): bool
    {
        return $this->detectionResult->riskLevel !== VpnRiskLevel::LOW;
    }

    /**
     * Check if notification should be sent to tenant owners
     */
    public function shouldNotifyTenantOwners(): bool
    {
        return $this->detectionResult->riskLevel->shouldNotifyTenantOwners();
    }

    /**
     * Check if notification should be sent to all stakeholders (owners + investors)
     */
    public function shouldNotifyAllStakeholders(): bool
    {
        return $this->detectionResult->riskLevel->shouldNotifyAllStakeholders();
    }

    /**
     * Get notification title
     */
    public function getNotificationTitle(): string
    {
        return match ($this->detectionResult->riskLevel) {
            VpnRiskLevel::LOW => 'VPN detected',
            VpnRiskLevel::MEDIUM => 'VPN detected - Medium Risk',
            VpnRiskLevel::HIGH => 'VPN detected - High Risk',
            VpnRiskLevel::CRITICAL => 'CRITICAL: VPN detected',
        };
    }

    /**
     * Get notification message
     */
    public function getNotificationMessage(): string
    {
        $provider = $this->detectionResult->provider ?? 'Unknown provider';
        $risk = $this->detectionResult->riskLevel->getLabel();
        $ip = $this->detectionResult->ipAddress;
        $country = $this->detectionResult->country ?? 'Unknown country';

        return sprintf(
            'Обнаружен вход через VPN: %s (%s). IP: %s, Страна: %s. Уровень риска: %s.',
            $provider,
            $this->detectionResult->isTor ? 'Tor Network' : 'VPN/Proxy',
            $ip,
            $country,
            $risk
        );
    }

    /**
     * Get event data for logging
     */
    public function toLogArray(): array
    {
        return [
            'event' => 'vpn_detected',
            'user_id' => $this->user->id,
            'tenant_id' => $this->user->tenant_id,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'correlation_id' => $this->correlationId,
            'vpn_provider' => $this->detectionResult->provider,
            'vpn_risk_level' => $this->detectionResult->riskLevel->value,
            'is_tor' => $this->detectionResult->isTor,
            'is_datacenter' => $this->detectionResult->isDatacenter,
            'is_residential_proxy' => $this->detectionResult->isResidentialProxy,
            'is_corporate_vpn' => $this->detectionResult->isCorporateVpn,
            'detection_sources' => $this->detectionResult->detectionSources,
            'country' => $this->detectionResult->country,
            'city' => $this->detectionResult->city,
            'has_behavioral_anomaly' => $this->detectionResult->hasBehavioralAnomaly,
        ];
    }
}
