<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\VpnDetectionService;
use App\Services\Security\ResidentialProxyDetectionService;
use App\Services\Security\CooldownService;
use App\Services\Security\AuditService;
use App\Enums\CooldownActionType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * VPN Protection Middleware
 *
 * Protects high-risk actions from VPN/Proxy when combined with fraud/ML/behavioral risk factors.
 *
 * VPN/Proxy alone does NOT block - only when combined with additional risk signals.
 * This middleware performs detection and applies cooldown if needed.
 * The existing HighRiskActionGuard checks if cooldown is active.
 */
final class VpnProtectionMiddleware
{
    public function __construct(
        private readonly VpnDetectionService $vpnDetectionService,
        private readonly ResidentialProxyDetectionService $residentialProxyDetectionService,
        private readonly CooldownService $cooldownService,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('vpn-protection.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Check if this is a high-risk action
        $actionType = $this->determineActionType($request);
        if (!$actionType) {
            return $next($request);
        }

        // Check VPN protection
        $vpnCheck = $this->vpnDetectionService->shouldApplyLimitTimeBlock($request, $user);
        
        // Check Residential Proxy protection
        $proxyCheck = $this->residentialProxyDetectionService->shouldApplyLimitTimeBlock($request, $user);

        // Determine if block should be applied
        $shouldBlock = $vpnCheck['should_block'] || $proxyCheck['should_block'];
        
        if ($shouldBlock) {
            $riskLevel = $vpnCheck['should_block'] ? $vpnCheck['risk_level'] : $proxyCheck['risk_level'];
            $riskFactors = array_merge(
                $vpnCheck['risk_factors'],
                $proxyCheck['risk_factors']
            );
            
            return $this->handleBlock($request, $user, $actionType, $riskLevel, $riskFactors);
        }

        // Log detection but don't block (VPN/Proxy alone)
        if ($vpnCheck['detection']->isVpn || $proxyCheck['detection']->isResidentialProxy) {
            $this->logDetectionOnly($request, $user, $vpnCheck, $proxyCheck);
        }

        return $next($request);
    }

    /**
     * Determine the action type based on the request
     */
    private function determineActionType(Request $request): ?string
    {
        $highRiskActions = config('vpn-protection.high_risk_actions', []);
        
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $routeName = $route->getName();
        if (!$routeName) {
            return null;
        }

        // Check financial actions
        foreach ($highRiskActions['financial'] ?? [] as $pattern) {
            if (str_contains($routeName, $pattern)) {
                return 'financial';
            }
        }

        // Check critical changes
        foreach ($highRiskActions['critical_changes'] ?? [] as $pattern) {
            if (str_contains($routeName, $pattern)) {
                return 'critical';
            }
        }

        return null;
    }

    /**
     * Handle block - apply cooldown and return error response
     */
    private function handleBlock(
        Request $request,
        $user,
        string $actionType,
        string $riskLevel,
        array $riskFactors
    ): Response {
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
        $this->cooldownService->startCooldown(
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
        $this->auditService->logEvent('high_risk_action_blocked', [
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'action_type' => $actionType,
            'route' => $request->route()?->getName(),
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

        // Return error response
        return response()->json([
            'error' => 'high_risk_action_blocked',
            'message' => $this->getBlockingMessage($riskLevel, $cooldownHours),
            'risk_level' => $riskLevel,
            'cooldown_hours' => $cooldownHours,
            'correlation_id' => $correlationId,
        ], 403);
    }

    /**
     * Log detection only (no block) for VPN/Proxy without additional risk factors
     */
    private function logDetectionOnly(
        Request $request,
        $user,
        array $vpnCheck,
        array $proxyCheck
    ): void {
        $this->auditService->logEvent('vpn_proxy_detected_no_block', [
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
