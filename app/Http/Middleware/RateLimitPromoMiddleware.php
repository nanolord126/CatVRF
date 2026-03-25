declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\RateLimitException;
use App\Services\Security\RateLimiterService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final /**
 * RateLimitPromoMiddleware
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RateLimitPromoMiddleware
{
    public function __construct(
        private RateLimiterService $rateLimiter,
    ) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}
    
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        $tenantId = auth()->user()?->tenant_id ?? 0;
        
        if (!$this->rateLimiter->checkPromoApply($tenantId, $correlationId)) {
            throw new RateLimitException();
        }
        
        return $next($request);
    }
}
