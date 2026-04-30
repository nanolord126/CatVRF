<?php

declare(strict_types=1);

namespace App\Listeners\Security;

use App\Events\Security\CooldownStarted;
use App\Enums\CooldownActionType;
use App\Enums\VpnRiskLevel;
use App\Models\User;
use App\Notifications\VpnBlockActivatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Notify Stakeholders on VPN Block Listener
 *
 * Sends notifications to tenant owners and investors when a VPN/proxy block
 * is activated for financial operations or critical changes.
 *
 * Production 2026 CANON:
 * - Queued for async processing
 * - Notifies all tenant owners for Medium+ risk
 * - Notifies all owners AND investors for High+ risk
 * - Includes detailed context about the detection
 * - Logs all notification attempts
 */
final class NotifyStakeholdersOnVpnBlockListener implements ShouldQueue
{
    public string $queue = 'security-notifications';

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CooldownStarted $event): void
    {
        $cooldown = $event->cooldown;
        $triggeredBy = $event->triggeredBy;

        // Only process VPN-related cooldowns
        if (!in_array($cooldown->action_type, [
            CooldownActionType::VPN_LOGIN->value,
            CooldownActionType::FINANCIAL_OPERATIONS->value,
            CooldownActionType::CRITICAL_CHANGES->value,
        ], true)) {
            return;
        }

        // Extract risk level from metadata if available
        $riskLevel = $cooldown->metadata['vpn_risk_level'] ?? $cooldown->metadata['proxy_risk_level'] ?? 'medium';
        $provider = $cooldown->metadata['vpn_provider'] ?? $cooldown->metadata['proxy_provider'] ?? 'Unknown';

        // Determine notification scope based on risk level
        $shouldNotifyInvestors = $this->shouldNotifyInvestors($riskLevel);

        // Get tenant ID
        $tenantId = $cooldown->tenant_id ?? $triggeredBy?->tenant_id;

        if ($tenantId === null) {
            Log::warning('Cannot notify stakeholders: no tenant ID', [
                'cooldown_id' => $cooldown->id,
            ]);
            return;
        }

        // Notify tenant owners
        $this->notifyTenantOwners($tenantId, $cooldown, $riskLevel, $provider);

        // Notify investors for High+ risk
        if ($shouldNotifyInvestors) {
            $this->notifyInvestors($tenantId, $cooldown, $riskLevel, $provider);
        }

        Log::info('VPN block stakeholder notifications sent', [
            'cooldown_id' => $cooldown->id,
            'tenant_id' => $tenantId,
            'risk_level' => $riskLevel,
            'notified_investors' => $shouldNotifyInvestors,
        ]);
    }

    /**
     * Check if investors should be notified based on risk level
     */
    private function shouldNotifyInvestors(string $riskLevel): bool
    {
        return in_array($riskLevel, [
            VpnRiskLevel::HIGH->value,
            VpnRiskLevel::CRITICAL->value,
            'high',
            'critical',
        ], true);
    }

    /**
     * Notify all tenant owners
     */
    private function notifyTenantOwners(
        int $tenantId,
        mixed $cooldown,
        string $riskLevel,
        string $provider
    ): void {
        $owners = User::whereHas('tenants', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                ->where('role', 'owner');
        })->get();

        foreach ($owners as $owner) {
            try {
                $owner->notify(new VpnBlockActivatedNotification(
                    tenantId: $tenantId,
                    riskLevel: $riskLevel,
                    provider: $provider,
                    actionType: $cooldown->action_type,
                    expiresAt: $cooldown->expires_at,
                    reason: $cooldown->reason,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to notify tenant owner', [
                    'user_id' => $owner->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notify all investors
     */
    private function notifyInvestors(
        int $tenantId,
        mixed $cooldown,
        string $riskLevel,
        string $provider
    ): void {
        // Assuming investors are users with 'investor' role or a separate investors table
        // Adjust this based on your actual data model
        $investors = User::whereHas('tenants', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                ->where('role', 'investor');
        })->get();

        foreach ($investors as $investor) {
            try {
                $investor->notify(new VpnBlockActivatedNotification(
                    tenantId: $tenantId,
                    riskLevel: $riskLevel,
                    provider: $provider,
                    actionType: $cooldown->action_type,
                    expiresAt: $cooldown->expires_at,
                    reason: $cooldown->reason,
                ));
            } catch (\Throwable $e) {
                Log::error('Failed to notify investor', [
                    'user_id' => $investor->id,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
