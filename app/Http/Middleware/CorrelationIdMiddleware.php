<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CorrelationIdMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
            Log::channel('audit')->debug('Correlation ID injected', [
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
}
