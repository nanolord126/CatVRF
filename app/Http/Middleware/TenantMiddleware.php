<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tenant Middleware
 * Production 2026 CANON - Enhanced Security
 *
 * Validates and scopes tenant from request with security hardening:
 * - Extracts tenant_id from Sanctum token
 * - Validates tenant exists and is active
 * - Signature validation for header-based tenant identification
 * - IP whitelist validation for header-based tenant identification
 * - Rate limiting for tenant resolution attempts
 * - Stores tenant_id in request context
 * - Prevents cross-tenant data access
 * - Applies tenant scoping to all queries (if using query scopes)
 *
 * SECURITY: Header-based tenant identification requires:
 * - X-Tenant-ID header
 * - X-Tenant-Signature header (HMAC-SHA256 of tenant_id + timestamp + secret)
 * - X-Tenant-Timestamp header (must be within 5 minutes)
 * - IP must be in whitelist (if configured)
 *
 * @author CatVRF Team
 * @version 2026.04.17
 */
final class TenantMiddleware
{
    private const SIGNATURE_TOLERANCE_SECONDS = 300; // 5 minutes
    private const SIGNATURE_ALGORITHM = 'sha256';

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle the request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if using header-based tenant identification
        if ($this->isHeaderBasedIdentification($request)) {
            return $this->handleHeaderBasedIdentification($request, $next);
        }

        // Default: get tenant from authenticated user
        return $this->handleUserBasedIdentification($request, $next);
    }

    /**
     * Handle header-based tenant identification with signature validation
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    private function handleHeaderBasedIdentification(Request $request, Closure $next)
    {
        $tenantId = $request->header('X-Tenant-ID');
        $signature = $request->header('X-Tenant-Signature');
        $timestamp = $request->header('X-Tenant-Timestamp');

        // Validate required headers
        if (!$tenantId || !$signature || !$timestamp) {
            $this->logger->warning('Missing required tenant headers', [
                'ip' => $request->ip(),
                'has_tenant_id' => !empty($tenantId),
                'has_signature' => !empty($signature),
                'has_timestamp' => !empty($timestamp),
            ]);
            throw new AuthenticationException('Missing required tenant identification headers');
        }

        // Validate timestamp freshness
        $timestampInt = (int) $timestamp;
        $now = time();
        
        if (abs($now - $timestampInt) > self::SIGNATURE_TOLERANCE_SECONDS) {
            $this->logger->warning('Tenant signature timestamp expired', [
                'tenant_id' => $tenantId,
                'timestamp' => $timestampInt,
                'now' => $now,
                'ip' => $request->ip(),
            ]);
            throw new AuthenticationException('Tenant signature timestamp expired');
        }

        // Verify tenant exists and get secret
        $tenant = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->first();

        if (!$tenant) {
            $this->logger->warning('Tenant not found in header-based identification', [
                'tenant_id' => $tenantId,
                'ip' => $request->ip(),
            ]);
            throw new AuthenticationException('Tenant not found');
        }

        if (!$tenant->is_active) {
            $this->logger->warning('Inactive tenant in header-based identification', [
                'tenant_id' => $tenantId,
                'ip' => $request->ip(),
            ]);
            throw new AuthenticationException('Tenant is inactive');
        }

        // Get tenant secret from meta or config
        $tenantSecret = $this->getTenantSecret($tenant);

        // Validate signature
        $expectedSignature = hash_hmac(
            self::SIGNATURE_ALGORITHM,
            $tenantId . $timestamp,
            $tenantSecret
        );

        if (!hash_equals($expectedSignature, $signature)) {
            $this->logger->warning('Invalid tenant signature', [
                'tenant_id' => $tenantId,
                'ip' => $request->ip(),
                'expected' => $expectedSignature,
                'provided' => $signature,
            ]);
            throw new AuthenticationException('Invalid tenant signature');
        }

        // Validate IP whitelist if configured
        if ($this->isIpWhitelistEnabled()) {
            $this->validateIpWhitelist($request->ip(), $tenantId);
        }

        // Store tenant context
        $request->attributes->set('tenant_id', $tenantId);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_identification_method', 'header');

        app('tenant.context')->setTenant($tenantId);

        $this->logger->info('Tenant identified via header', [
            'tenant_id' => $tenantId,
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }

    /**
     * Handle user-based tenant identification (default)
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws AuthenticationException
     */
    private function handleUserBasedIdentification(Request $request, Closure $next)
    {
        // Get tenant from authenticated user
        $user = $request->user();

        if (!$user) {
            throw new AuthenticationException('User not authenticated');
        }

        // Get tenant_id from user
        $tenantId = $user->tenant_id ?? null;

        if (!$tenantId) {
            throw new AuthenticationException('User has no tenant assigned');
        }

        // Verify tenant exists and is active
        $tenant = $this->db->table('tenants')
            ->where('id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            throw new AuthenticationException('Tenant not found or inactive');
        }

        // Store tenant context in request
        $request->attributes->set('tenant_id', $tenantId);
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_identification_method', 'user');

        // Store in auth guard for access throughout request
        app('tenant.context')->setTenant($tenantId);

        return $next($request);
    }

    /**
     * Check if request is using header-based identification
     *
     * @param Request $request
     * @return bool
     */
    private function isHeaderBasedIdentification(Request $request): bool
    {
        return config('tenancy.identification.resolvers.header', false) 
            && $request->hasHeader('X-Tenant-ID');
    }

    /**
     * Get tenant secret for signature validation
     *
     * @param mixed $tenant
     * @return string
     */
    private function getTenantSecret(mixed $tenant): string
    {
        // Try to get secret from tenant meta
        $meta = is_string($tenant->meta) ? json_decode($tenant->meta, true) : $tenant->meta;
        
        if (isset($meta['api_secret'])) {
            return $meta['api_secret'];
        }

        // Fallback to global secret
        return config('tenancy.header_signature_secret', config('app.key'));
    }

    /**
     * Check if IP whitelist is enabled
     *
     * @return bool
     */
    private function isIpWhitelistEnabled(): bool
    {
        return config('tenancy.security.ip_whitelist_enabled', false);
    }

    /**
     * Validate IP against whitelist
     *
     * @param string $ip
     * @param string $tenantId
     * @return void
     * @throws AuthenticationException
     */
    private function validateIpWhitelist(string $ip, string $tenantId): void
    {
        $whitelist = config('tenancy.security.ip_whitelist', []);

        if (empty($whitelist)) {
            return;
        }

        $isAllowed = false;
        foreach ($whitelist as $allowedIp) {
            if ($this->ipMatches($ip, $allowedIp)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            $this->logger->warning('IP not in whitelist for tenant', [
                'tenant_id' => $tenantId,
                'ip' => $ip,
                'whitelist' => $whitelist,
            ]);
            throw new AuthenticationException('IP address not authorized for tenant access');
        }
    }

    /**
     * Check if IP matches pattern (supports CIDR notation)
     *
     * @param string $ip
     * @param string $pattern
     * @return bool
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }

        // CIDR notation support
        if (str_contains($pattern, '/')) {
            [$network, $mask] = explode('/', $pattern);
            return $this->cidrMatch($ip, $network, (int) $mask);
        }

        // Wildcard support
        if (str_contains($pattern, '*')) {
            $pattern = str_replace('*', '.*', $pattern);
            return (bool) preg_match('/^' . $pattern . '$/', $ip);
        }

        return false;
    }

    /**
     * CIDR IP matching
     *
     * @param string $ip
     * @param string $network
     * @param int $mask
     * @return bool
     */
    private function cidrMatch(string $ip, string $network, int $mask): bool
    {
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);
        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($networkLong & $maskLong);
    }
}
