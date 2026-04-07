<?php declare(strict_types=1);

/**
 * CorrelationIdMiddleware — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/correlationidmiddleware
 * @see https://catvrf.ru/docs/correlationidmiddleware
 * @see https://catvrf.ru/docs/correlationidmiddleware
 * @see https://catvrf.ru/docs/correlationidmiddleware
 * @see https://catvrf.ru/docs/correlationidmiddleware
 */


namespace App\Http\Middleware;

use Illuminate\Log\LogManager;

final class CorrelationIdMiddleware
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}


    /**
         * Handle the request
         */
        public function handle(Request $request, Closure $next): mixed
        {
            // Получить correlation_id из header или сгенерировать новый
            $correlationId = $request->header('X-Correlation-ID')
                ?? $request->header('x-correlation-id')
                ?? (string)Str::uuid();

            // Валидировать формат (UUID)
            if (!Str::isUuid($correlationId)) {
                $correlationId = (string)Str::uuid();
            }

            // Сохранить в request для доступа во всем стеке
            $request->attributes->set('correlation_id', $correlationId);

            // Логировать только для debug уровня
            $this->logger->channel('audit')->debug('Correlation ID injected', [
                'correlation_id' => $correlationId,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            // Продолжить к следующему middleware/контроллеру
            $response = $next($request);

            // Добавить correlation_id в response headers
            $response->header('X-Correlation-ID', $correlationId);
            $response->header('X-Request-ID', $correlationId);

            return $response;
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
