<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class IpWhitelistMiddleware
{
    /**
     * Проверить, находится ли IP в whitelist.
     *
     * Использование:
     *   Route::middleware([IpWhitelistMiddleware::class . ':webhook'])
     *   Route::middleware([IpWhitelistMiddleware::class . ':admin'])
     *
     * @param Request $request
     * @param Closure $next
     * @param string $whitelist Ключ конфига (webhook, admin, partner и т.д.)
     * @return Response
     */
    public function handle(
        Request $request,
        Closure $next,
        string $whitelist = 'webhook'
    ): Response {
        $clientIp = $this->getClientIp($request);
        $allowedIps = config("security.ip_whitelist.{$whitelist}", []);
        
        if (empty($allowedIps)) {
            Log::channel('security')->warning('IP whitelist not configured', [
                'whitelist' => $whitelist,
            ]);
            return response()->json(['error' => 'IP whitelist not configured'], 500);
        }
        
        if (!$this->isIpWhitelisted($clientIp, $allowedIps)) {
            Log::channel('fraud_alert')->warning('IP blocked by whitelist', [
                'whitelist' => $whitelist,
                'client_ip' => $clientIp,
                'endpoint' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return response()->json(['error' => 'IP not whitelisted'], 403);
        }
        
        Log::channel('audit')->debug('IP whitelisted', [
            'whitelist' => $whitelist,
            'client_ip' => $clientIp,
        ]);
        
        return $next($request);
    }
    
    /**
     * Получить реальный IP клиента.
     *
     * Проверяет:
     * 1. CF-Connecting-IP (Cloudflare)
     * 2. X-Forwarded-For (nginx reverse proxy)
     * 3. X-Real-IP (nginx)
     * 4. REMOTE_ADDR (fallback)
     *
     * @param Request $request
     * @return string
     */
    private function getClientIp(Request $request): string
    {
        // Если за Cloudflare
        if ($request->hasHeader('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }
        
        // Если за nginx reverse proxy
        if ($request->hasHeader('X-Forwarded-For')) {
            $ips = array_map('trim', explode(',', $request->header('X-Forwarded-For')));
            return $ips[0] ?? '';
        }
        
        if ($request->hasHeader('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }
        
        return $request->ip() ?? '0.0.0.0';
    }
    
    /**
     * Проверить, находится ли IP в whitelist.
     *
     * Поддерживает:
     * - Точный IP: 10.0.0.1
     * - CIDR range: 10.0.0.0/8
     * - Wildcard: 10.0.*
     *
     * @param string $ip
     * @param array $whitelist
     * @return bool
     */
    private function isIpWhitelisted(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowed) {
            if ($this->ipMatches($ip, $allowed)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Проверить, совпадает ли IP с pattern.
     *
     * @param string $ip
     * @param string $pattern Может быть точный IP, CIDR, или wildcard
     * @return bool
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Точное совпадение
        if ($ip === $pattern) {
            return true;
        }
        
        // CIDR notation
        if (str_contains($pattern, '/')) {
            return $this->ipInCidr($ip, $pattern);
        }
        
        // Wildcard (10.0.*)
        if (str_contains($pattern, '*')) {
            $pattern = str_replace('*', '.*', preg_quote($pattern));
            return (bool)preg_match('/^' . $pattern . '$/', $ip);
        }
        
        return false;
    }
    
    /**
     * Проверить, находится ли IP в CIDR range.
     *
     * @param string $ip IPv4 адрес
     * @param string $cidr CIDR notation (10.0.0.0/8)
     * @return bool
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }
        
        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int)$bits;
        
        // Валидировать CIDR
        if ($bits < 0 || $bits > 32) {
            return false;
        }
        
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        
        if ($ip_long === false || $subnet_long === false) {
            return false;
        }
        
        $mask = -1 << (32 - $bits);
        $subnet_long &= $mask;
        
        return ($ip_long & $mask) === $subnet_long;
    }
}
