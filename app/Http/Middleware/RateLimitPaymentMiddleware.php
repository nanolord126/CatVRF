<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RateLimitPaymentMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

            if (!$this->rateLimiter->checkPaymentInit(
                $tenantId,
                auth()->id() ?? 0,
                $correlationId
            )) {
                throw new RateLimitException();
            }

            return $next($request);
        }
}
