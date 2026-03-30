<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalAuditMiddleware extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Обработка запроса: аудит, лимиты, заголовки.
         *
         * @param Request $request
         * @param Closure $next
         * @return mixed
         */
        public function handle(Request $request, Closure $next): mixed
        {
            // 1. Установка сквозного correlation_id для всех логов и ответов
            $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
            $request->attributes->set('correlation_id', $correlationId);

            // 2. Начальный лог обращения (до обработки)
            Log::channel('audit')->info('Medical API Request Initialized', [
                'correlation_id' => $correlationId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => auth()->id() ?? 'guest',
                'tenant_id' => auth()->user()?->tenant_id ?? 'unknown',
                'payload_size' => strlen($request->getContent()),
            ]);

            // 3. Дополнительная защита: проверка на подозрительные сканы ФЗ-152 данных
            if ($request->isMethod('GET') && $this->isSensitivePath($request->path())) {
                $this->checkRequestDensity($request, $correlationId);
            }

            // 4. Обработка запроса
            $response = $next($request);

            // 5. Финальный лог с результатом и заголовком
            $response->headers->set('X-Correlation-ID', $correlationId);

            Log::channel('audit')->info('Medical API Request Completed', [
                'correlation_id' => $correlationId,
                'status' => $response->getStatusCode(),
                'execution_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2),
            ]);

            return $response;
        }

        /**
         * Проверка на обращение к персональным медицинским данным.
         *
         * @param string $path
         * @return bool
         */
        private function isSensitivePath(string $path): bool
        {
            return str_contains($path, '/medical-records/') ||
                   str_contains($path, '/patients/') ||
                   str_contains($path, '/prescriptions/');
        }

        /**
         * Защита от массового слива данных (Data Scraper Protection).
         * ФЗ-152 требует предотвращать несанкционированный доступ.
         *
         * @param Request $request
         * @param string $correlationId
         */
        private function checkRequestDensity(Request $request, string $correlationId): void
        {
            $userId = auth()->id() ?? $request->ip();
            $key = "audit_scan:{$userId}:" . now()->format('Y-m-d-H');

            $count = \Illuminate\Support\Facades\Redis::incr($key);
            \Illuminate\Support\Facades\Redis::expire($key, 3600);

            // Порог: 200 приватных записей в час для врача (лимит по умолчанию в 2026)
            if ($count > 200 && !auth()->user()?->hasRole('admin')) {
                Log::channel('fraud_alert')->error('SUSPECTED DATA SCRAPING DETECTED (Medical Records Scan)', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'request_count' => $count,
                    'ip' => $request->ip(),
                ]);

                // Блокировка на 1 час (опционально по канону 2026)
                // abort(429, 'Excessive medical record access detected. Security audit required.');
            }
        }
}
