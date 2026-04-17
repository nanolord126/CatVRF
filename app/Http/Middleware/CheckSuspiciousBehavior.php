<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SuspiciousBehaviorDetector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class CheckSuspiciousBehavior
{
    public function __construct(
        private readonly SuspiciousBehaviorDetector $behaviorDetector,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id ?? 0;
        $userGender = $request->user()?->gender ?? 'unknown';
        $correlationId = $request->header('X-Correlation-ID', '');

        // Проверяем только для маршрутов примерочной нижнего белья
        if ($this->isLingerieFittingRoute($request)) {
            $result = $this->behaviorDetector->checkLingerieFittingAccess(
                $userId,
                $userGender,
                $correlationId
            );

            if (!$result['allowed']) {
                Log::channel('security')->warning('Access denied by suspicious behavior middleware', [
                    'user_id' => $userId,
                    'user_gender' => $userGender,
                    'reason' => $result['reason'] ?? 'unknown',
                    'correlation_id' => $correlationId,
                    'route' => $request->route()?->getName(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $result['message'],
                    'reason' => $result['reason'] ?? 'unknown',
                    'block_expires_at' => $result['block_expires_at'] ?? null,
                    'correlation_id' => $correlationId,
                ], 403);
            }

            // Если есть предупреждение, добавляем заголовок
            if (isset($result['warning'])) {
                $request->headers->set('X-Suspicion-Warning', $result['warning']);
            }
        }

        return $next($request);
    }

    /**
     * Проверяет, является ли маршрут примерочной нижнего белья
     */
    private function isLingerieFittingRoute(Request $request): bool
    {
        $route = $request->route();
        
        if (!$route) {
            return false;
        }

        $routeName = $route->getName();
        
        // Маршруты примерочной нижнего белья
        $protectedRoutes = [
            'fashion.fitting.check-access',
            'fashion.fitting.recommendations',
            'fashion.fitting.save-measurements',
        ];

        return in_array($routeName, $protectedRoutes);
    }
}
