<?php

declare(strict_types=1);

namespace App\Traits;

use App\Services\Security\VpnDetectionService;
use App\Services\Security\ResidentialProxyDetectionService;
use App\Services\Security\CooldownService;
use App\Services\Security\AuditService;
use App\Enums\CooldownActionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Checks VPN Protection Trait
 *
 * Use this trait in controllers or services to check VPN protection before high-risk actions.
 * 
 * Usage:
 * ```php
 * use App\Traits\ChecksVpnProtection;
 * 
 * class WalletController extends Controller
 * {
 *     use ChecksVpnProtection;
 *     
 *     public function withdraw(Request $request)
 *     {
 *         $check = $this->checkVpnProtection($request, $user, 'financial');
 *         if ($check['should_block']) {
 *             return response()->json(['error' => $check['message']], 403);
 *         }
 *         
 *         // Proceed with withdrawal
 *     }
 * }
 * ```
 */
trait ChecksVpnProtection
{
    /**
     * Check if VPN protection should block the action
     *
     * @param  Request  $request
     * @param  \App\Models\User  $user
     * @param  string  $actionType  'financial' or 'critical'
     * @return array{should_block: bool, message?: string, risk_level?: string, cooldown_hours?: int}
     */
    protected function checkVpnProtection(Request $request, $user, string $actionType): array
    {
        if (!config('vpn-protection.enabled', true)) {
            return ['should_block' => false];
        }

        $vpnDetectionService = app(VpnDetectionService::class);
        $residentialProxyDetectionService = app(ResidentialProxyDetectionService::class);
        $cooldownService = app(CooldownService::class);
        $auditService = app(AuditService::class);

        // Check VPN protection
        $vpnCheck = $vpnDetectionService->shouldApplyLimitTimeBlock($request, $user);
        
        // Check Residential Proxy protection
        $proxyCheck = $residentialProxyDetectionService->shouldApplyLimitTimeBlock($request, $user);

        // Determine if block should be applied
        $shouldBlock = $vpnCheck['should_block'] || $proxyCheck['should_block'];
        
        if ($shouldBlock) {
            $riskLevel = $vpnCheck['should_block'] ? $vpnCheck['risk_level'] : $proxyCheck['risk_level'];
            $riskFactors = array_merge(
                $vpnCheck['risk_factors'],
                $proxyCheck['risk_factors']
            );
            
            return $this->handleBlock(
                $request,
                $user,
                $actionType,
                $riskLevel,
                $riskFactors,
                $cooldownService,
                $auditService
            );
        }

        // Log detection but don't block (VPN/Proxy alone)
        if ($vpnCheck['detection']->isVpn || $proxyCheck['detection']->isResidentialProxy) {
            $this->logDetectionOnly($request, $user, $vpnCheck, $proxyCheck, $auditService);
        }

        return ['should_block' => false];
    }

    /**
     * Handle block - apply cooldown and return error response data
     *
     * @return array{should_block: true, message: string, risk_level: string, cooldown_hours: int}
     */
    private function handleBlock(
        Request $request,
        $user,
        string $actionType,
        string $riskLevel,
        array $riskFactors,
        CooldownService $cooldownService,
        AuditService $auditService
    ): array {
        $protectionConfig = config("vpn-protection.protection_levels.{$riskLevel}", []);
        $cooldownHours = $protectionConfig['cooldown_hours'] ?? 24;
        
        $correlationId = (string) \Illuminate\Support\Str::uuid();

        // Determine cooldown action type
        $cooldownActionType = match ($actionType) {
            'financial' => CooldownActionType::FINANCIAL_OPERATIONS,
            'critical' => CooldownActionType::CRITICAL_CHANGES,
            default => CooldownActionType::VPN_LOGIN,
        };

        // Apply cooldown
        $cooldownService->startCooldown(
            $user,
            $cooldownActionType,
            $cooldownHours,
            sprintf(
                'High-risk action blocked due to VPN/Proxy + risk factors (Risk: %s)',
                $riskLevel
            ),
            $user->tenant_id,
            [
                'action_type' => $actionType,
                'risk_level' => $riskLevel,
                'risk_factors' => $riskFactors,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'correlation_id' => $correlationId,
            ]
        );

        // Log to audit
        $auditService->logEvent('high_risk_action_blocked', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action_type' => $actionType,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'cooldown_hours' => $cooldownHours,
            'ip_address' => $request->ip(),
            'correlation_id' => $correlationId,
        ], 'security');

        // Log to security channel
        Log::channel('security')->warning('High-risk action blocked by VPN protection', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action_type' => $actionType,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'ip_address' => $request->ip(),
            'correlation_id' => $correlationId,
        ]);

        return [
            'should_block' => true,
            'message' => $this->getBlockingMessage($riskLevel, $cooldownHours),
            'risk_level' => $riskLevel,
            'cooldown_hours' => $cooldownHours,
            'correlation_id' => $correlationId,
        ];
    }

    /**
     * Log detection only (no block) for VPN/Proxy without additional risk factors
     */
    private function logDetectionOnly(
        Request $request,
        $user,
        array $vpnCheck,
        array $proxyCheck,
        AuditService $auditService
    ): void {
        $auditService->logEvent('vpn_proxy_detected_no_block', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'vpn_detected' => $vpnCheck['detection']->isVpn,
            'proxy_detected' => $proxyCheck['detection']->isResidentialProxy,
            'ip_address' => $request->ip(),
        ], 'security');

        Log::channel('security')->info('VPN/Proxy detected but no block (no additional risk factors)', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Get user-friendly blocking message
     */
    private function getBlockingMessage(string $riskLevel, int $hours): string
    {
        return match ($riskLevel) {
            'critical' => "Due to detected VPN/proxy combined with suspicious activity, financial operations are temporarily restricted for {$hours} hours. For immediate assistance, please contact support.",
            'high' => "Due to detected VPN/proxy combined with risk factors, financial operations are temporarily restricted for {$hours} hours. Please try again later or contact support.",
            default => "This action is temporarily restricted. Please try again later.",
        };
    }
}
