<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP Whitelist Middleware for Landlord Admin Panel
 * 
 * Restricts access to /admin (landlord panel) to whitelisted IPs only.
 * Configured via config/filament.php -> admin.ip_whitelist.
 * 
 * @package App\Http\Middleware
 */
final readonly class FilamentAdminIpWhitelist
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to admin panel
        if (!$request->is('admin/*') && !$request->is('admin')) {
            return $next($request);
        }

        $whitelist = config('filament.admin.ip_whitelist', '');

        // If whitelist is empty, allow all (development mode)
        if (empty($whitelist)) {
            return $next($request);
        }

        // Convert comma-separated string to array
        $whitelistArray = is_array($whitelist) ? $whitelist : array_map('trim', explode(',', $whitelist));

        $clientIp = $request->ip();

        // Check if IP is whitelisted
        if (!$this->isIpWhitelisted($clientIp, $whitelistArray)) {
            abort(403, 'Access denied: Your IP address is not whitelisted for admin access.');
        }

        return $next($request);
    }

    /**
     * Check if IP is whitelisted (supports CIDR notation)
     */
    private function isIpWhitelisted(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowedIp) {
            if ($this->ipInRange($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in range (supports CIDR)
     */
    private function ipInRange(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }

        // CIDR notation
        if (str_contains($range, '/')) {
            [$range, $netmask] = explode('/', $range, 2);
            $netmask = (int) $netmask;

            if ($netmask < 0 || $netmask > 32) {
                return false;
            }

            $rangeDecimal = ip2long($range);
            $ipDecimal = ip2long($ip);
            $maskDecimal = ~((1 << (32 - $netmask)) - 1);

            return ($ipDecimal & $maskDecimal) === ($rangeDecimal & $maskDecimal);
        }

        return false;
    }
}
