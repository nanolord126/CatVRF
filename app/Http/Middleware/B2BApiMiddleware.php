<?php declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\B2B\B2BApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BApiMiddleware — авторизация B2B-запросов по API-ключу.
 *
 * Ожидает заголовок: X-B2B-API-Key: b2b_<token>
 *
 * После успешной валидации добавляет в Request:
 *   - is_b2b = true
 *   - business_group_id = <id>
 *   - b2b_business_group = BusinessGroup объект
 *
 * Middleware регистрируется в:
 *   - RouteServiceProvider или bootstrap/app.php → 'b2b.api'
 *   - Применяется ко всем routes/api/b2b.php маршрутам
 */
final class B2BApiMiddleware
{
    public function __construct(
        private readonly B2BApiKeyService $keyService,
        private readonly ResponseFactory $response,
    ) {}

    public function handle(Request $request, Closure $next, string $permission = ''): Response
    {
        $key = $request->header('X-B2B-API-Key');

        if (empty($key)) {
            return $this->response->json([
                'error'   => 'B2B API Key required',
                'message' => 'Pass X-B2B-API-Key header',
            ], 401);
        }

        try {
            $businessGroup = $this->keyService->validate((string) $key, $permission);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return $this->response->json([
                'error'   => 'Unauthorized',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        // Обогащаем Request данными о группе
        $request->merge([
            'is_b2b'             => true,
            'business_group_id'  => $businessGroup->id,
            'b2b_tenant_id'      => $businessGroup->tenant_id,
        ]);

        $request->attributes->set('b2b_business_group', $businessGroup);

        return $next($request);
    }
}
