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
 * RateLimitSearchMiddleware
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class RateLimitSearchMiddleware
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
        $userId = auth()->id() ?? 0;
        
        $isHeavy = $request->boolean('with_recommendations') 
                || $request->boolean('with_embeddings');
        
        if (!$this->rateLimiter->checkSearch($userId, $isHeavy, $correlationId)) {
            throw new RateLimitException();
        }
        
        return $next($request);
    }
}
