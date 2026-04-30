<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\DTO\Security\VpnDetectionResult;
use App\Enums\VpnRiskLevel;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * VPN Authentication Integration Service
 *
 * Integrates VPN detection into authentication flows.
 * Creates immutable audit records in user_logins and security_events.
 * Updates device information with VPN status.
 *
 * Production 2026 CANON:
 * - Always log VPN detections in immutable audit
 * - Apply gradient protection measures based on risk
 * - Notify stakeholders according to risk level
 * - Integrate with Cooldown, SplitKey, and Device Management
 */
final readonly class VpnAuthenticationIntegrationService
{
    public function __construct(
        private readonly VpnDetectionService $vpnDetection,
        private readonly CooldownService $cooldownService,
        private readonly AuditService $auditService,
        private readonly UserDeviceService $userDeviceService,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Process VPN detection after successful authentication
     *
     * @param  User  $user
     * @param  Request  $request
     * @param  string  $authMethod
     * @param  UserDevice|null  $device
     * @return array{vpn_detected: bool, risk_level: string|null, protection_applied: bool}
     */
    public function processSuccessfulLogin(
        User $user,
        Request $request,
        string $authMethod = 'password',
        ?UserDevice $device = null
    ): array {
        $correlationId = (string) Str::uuid();

        // Perform VPN detection
        $vpnResult = $this->vpnDetection->detect($request, $user);

        // Create immutable audit record in user_logins
        $this->createUserLoginRecord($user, $request, $authMethod, $vpnResult, $device, $correlationId);

        // Create security event if VPN detected
        if ($vpnResult->isVpn) {
            $this->createSecurityEvent($user, $request, $vpnResult, $correlationId);
        }

        // Update device with VPN information
        if ($device !== null) {
            $this->updateDeviceVpnInfo($device, $vpnResult);
        }

        // Apply protection measures based on risk
        $protectionApplied = false;
        if ($vpnResult->requiresProtection()) {
            $this->vpnDetection->applyProtection($user, $vpnResult, $correlationId);
            $protectionApplied = true;
        }

        // Log the authentication with VPN context
        $this->auditService->logEvent('user_authenticated_with_vpn_check', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'auth_method' => $authMethod,
            'vpn_detected' => $vpnResult->isVpn,
            'vpn_provider' => $vpnResult->provider,
            'vpn_risk_level' => $vpnResult->riskLevel->value,
            'protection_applied' => $protectionApplied,
            'ip_address' => $vpnResult->ipAddress,
            'correlation_id' => $correlationId,
        ], 'security');

        return [
            'vpn_detected' => $vpnResult->isVpn,
            'risk_level' => $vpnResult->riskLevel->value,
            'protection_applied' => $protectionApplied,
        ];
    }

    /**
     * Process VPN detection for failed authentication
     *
     * @param  User|null  $user
     * @param  Request  $request
     * @param  string  $failureReason
     * @param  string  $authMethod
     * @return void
     */
    public function processFailedLogin(
        ?User $user,
        Request $request,
        string $failureReason,
        string $authMethod = 'password'
    ): void {
        $correlationId = (string) Str::uuid();

        // Perform VPN detection even for failed logins
        $vpnResult = $this->vpnDetection->detect($request, $user);

        // Create audit record for failed login
        if ($user !== null) {
            $this->createUserLoginRecord(
                $user,
                $request,
                $authMethod,
                $vpnResult,
                null,
                $correlationId,
                false,
                $failureReason
            );
        }

        // Log failed authentication with VPN context
        $this->auditService->logEvent('user_authentication_failed_with_vpn_check', [
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'auth_method' => $authMethod,
            'failure_reason' => $failureReason,
            'vpn_detected' => $vpnResult->isVpn,
            'vpn_provider' => $vpnResult->provider,
            'vpn_risk_level' => $vpnResult->riskLevel->value,
            'ip_address' => $vpnResult->ipAddress,
            'correlation_id' => $correlationId,
        ], 'security');
    }

    /**
     * Create immutable audit record in user_logins
     *
     * @param  User  $user
     * @param  Request  $request
     * @param  string  $authMethod
     * @param  VpnDetectionResult  $vpnResult
     * @param  UserDevice|null  $device
     * @param  string  $correlationId
     * @param  bool  $wasSuccessful
     * @param  string|null  $failureReason
     * @return UserLogin
     */
    private function createUserLoginRecord(
        User $user,
        Request $request,
        string $authMethod,
        VpnDetectionResult $vpnResult,
        ?UserDevice $device,
        string $correlationId,
        bool $wasSuccessful = true,
        ?string $failureReason = null
    ): UserLogin {
        return UserLogin::create([
            'login_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'device_id' => $device?->id,
            'is_vpn' => $vpnResult->isVpn,
            'vpn_provider' => $vpnResult->provider,
            'vpn_risk_level' => $vpnResult->riskLevel->value,
            'ip_address' => $vpnResult->ipAddress,
            'country_code' => $vpnResult->country,
            'country_name' => $vpnResult->country,
            'city' => $vpnResult->city,
            'asn' => $vpnResult->asn,
            'isp' => $vpnResult->isp,
            'user_agent' => $request->userAgent(),
            'device_type' => $device?->device_type,
            'platform' => $device?->platform,
            'browser' => $device?->browser,
            'is_tor' => $vpnResult->isTor,
            'is_datacenter' => $vpnResult->isDatacenter,
            'is_residential_proxy' => $vpnResult->isResidentialProxy,
            'is_corporate_vpn' => $vpnResult->isCorporateVpn,
            'has_behavioral_anomaly' => $vpnResult->hasBehavioralAnomaly,
            'detection_sources' => $vpnResult->detectionSources,
            'metadata' => $vpnResult->metadata,
            'auth_method' => $authMethod,
            'was_successful' => $wasSuccessful,
            'failure_reason' => $failureReason,
            'logged_in_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Create security event for VPN detection
     *
     * @param  User  $user
     * @param  Request  $request
     * @param  VpnDetectionResult  $vpnResult
     * @param  string  $correlationId
     * @return void
     */
    private function createSecurityEvent(
        User $user,
        Request $request,
        VpnDetectionResult $vpnResult,
        string $correlationId
    ): void {
        $severity = match ($vpnResult->riskLevel) {
            VpnRiskLevel::LOW => 'info',
            VpnRiskLevel::MEDIUM => 'warning',
            VpnRiskLevel::HIGH => 'warning',
            VpnRiskLevel::CRITICAL => 'critical',
        };
$this->db->
        DB::table('security_events')->insert([
            'event_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'event_type' => 'vpn_detected',
            'severity' => $severity,
            'source_ip' => $vpnResult->ipAddress,
            'user_agent' => $request->userAgent(),
            'is_vpn' => true,
            'vpn_provider' => $vpnResult->provider,
            'vpn_risk_level' => $vpnResult->riskLevel->value,
            'is_tor' => $vpnResult->isTor,
            'is_datacenter' => $vpnResult->isDatacenter,
            'is_residential_proxy' => $vpnResult->isResidentialProxy,
            'is_corporate_vpn' => $vpnResult->isCorporateVpn,
            'has_behavioral_anomaly' => $vpnResult->hasBehavioralAnomaly,
            'country_code' => $vpnResult->country,
            'country_name' => $vpnResult->country,
            'city' => $vpnResult->city,
            'detection_sources' => json_encode($vpnResult->detectionSources),
            'metadata' => json_encode(array_merge($vpnResult->metadata, [
                'correlation_id' => $correlationId,
                'auth_context' => 'login',
            ])),
            'detected_at' => CarbonImmutable::now(),
            'correlation_id' => $correlationId,
            'created_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Update device with VPN information
     *
     * @param  UserDevice  $device
     * @param  VpnDetectionResult  $vpnResult
     * @return void
     */
    private function updateDeviceVpnInfo(UserDevice $device, VpnDetectionResult $vpnResult): void
    {
        $device->updateVpnInfo(
            isVpn: $vpnResult->isVpn,
            provider: $vpnResult->provider,
            riskLevel: $vpnResult->riskLevel,
            detectionData: $vpnResult->toArray()
        );
    }

    /**
     * Check if user can perform sensitive operation based on VPN status
     *
     * @param  User  $user
     * @param  Request  $request
     * @return array{allowed: bool, reason: string|null, cooldown_remaining: int}
     */
    public function canPerformSensitiveOperation(User $user, Request $request): array
    {
        // Check if user is under VPN cooldown
        if ($this->cooldownService->isUnderCooldown($user, \App\Enums\CooldownActionType::VPN_LOGIN)) {
            $remainingSeconds = $this->cooldownService->getRemainingTime(
                $user,
                \App\Enums\CooldownActionType::VPN_LOGIN
            );

            return [
                'allowed' => false,
                'reason' => 'VPN cooldown active',
                'cooldown_remaining' => $remainingSeconds,
            ];
        }

        // Perform VPN detection
        $vpnResult = $this->vpnDetection->detect($request, $user);

        // Block high/critical risk operations
        if ($vpnResult->isVpn && in_array($vpnResult->riskLevel, [VpnRiskLevel::HIGH, VpnRiskLevel::CRITICAL], true)) {
            return [
                'allowed' => false,
                'reason' => 'High-risk VPN detected',
                'cooldown_remaining' => 0,
            ];
        }

        // Require fresh Passkey for medium+ risk
        if ($vpnResult->isVpn && $vpnResult->riskLevel !== VpnRiskLevel::LOW) {
            return [
                'allowed' => true,
                'reason' => 'fresh_passkey_required',
                'cooldown_remaining' => 0,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'cooldown_remaining' => 0,
        ];
    }
}
