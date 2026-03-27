<?php declare(strict_types=1);
namespace App\Http\Controllers\Api;

use App\Services\FraudControlService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BaseApiController — Базовый контроллер для всех API-контроллеров
 * 
 * Включает общие методы:
 * - $this->isB2C() / $this->isB2B()
 * - $this->auditLog()
 * - $this->successResponse() / $this->errorResponse()
 * - $this->getCorrelationId()
 * 
 * Middleware применяются в Routes.
 * 
 * PRODUCTION-READY 2026 CANON
 * 
 * @author CatVRF Team
 * @version 2026.03.27
 */
abstract class BaseApiController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    public function __construct()
    {
    }

    /**
     * Получить correlation_id для запроса
     */
    protected function getCorrelationId(): string
    {
        return request()->get('correlation_id') 
            ?? request()->header('X-Correlation-ID') 
            ?? \Illuminate\Support\Str::uuid()->toString();
    }

    /**
     * Проверить режим B2C/B2B из middleware
     */
    protected function isB2C(): bool
    {
        return request()->get('b2c_mode') === true;
    }

    protected function isB2B(): bool
    {
        return request()->get('b2b_mode') === true;
    }

    protected function getModeType(): string
    {
        return (string)request()->get('mode_type', 'b2c');
    }

    /**
     * Лог аудита с correlation_id
     */
    protected function auditLog(string $action, array $data = []): void
    {
        Log::channel('audit')->info($action, array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'tenant_id' => filament()->getTenant()?->id,
            'mode' => $this->getModeType(),
        ], $data));
    }
    /**
     * Ответ успеха с correlation_id
     */
    protected function successResponse(mixed $data, string $message = 'Success', int $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'correlation_id' => $this->getCorrelationId(),
        ], $code);
    }
    /**
     * Ответ ошибки с correlation_id
     */
    protected function errorResponse(string $message, int $code = 400, array $errors = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'correlation_id' => $this->getCorrelationId(),
        ], $code);
    }
    /**
     * Лог фрода с full stack trace
     */
    protected function fraudLog(string $reason, array $context = []): void
    {
        Log::channel('fraud_alert')->warning("Fraud attempt: {$reason}", array_merge([
            'correlation_id' => $this->getCorrelationId(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'endpoint' => request()->path(),
            'trace' => debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ], $context));
    }
}
