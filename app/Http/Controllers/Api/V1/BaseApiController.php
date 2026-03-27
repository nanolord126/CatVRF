declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use Illuminate\Routing\Controller;
/**
 * Base API Controller.
 * Все API контроллеры должны наследовать этот класс.
 *
 * Features:
 * - correlation_id handling
 * - JSON response formatting
 * - Error handling
 * - Audit logging base
 */
abstract class BaseApiController extends Controller
{
    /**
     * Получить correlation_id из запроса.
     */
    protected function getCorrelationId(): string
    {
        return request()->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString());
    }
    /**
     * Получить tenant_id из текущего юзера.
     */
    protected function getTenantId(): int
    {
        return auth()->user()?->tenant_id ?? filament()->getTenant()?->id ?? 1;
    }
    /**
     * Успешный ответ (200 OK).
     */
    protected function success(array $data = [], string $message = 'Success'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
            'data' => $data,
        ], 200);
    }
    /**
     * Ошибка валидации (422 Unprocessable Entity).
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
            'errors' => $errors,
        ], 422);
    }
    /**
     * Ошибка авторизации (403 Forbidden).
     */
    protected function forbidden(string $message = 'Forbidden'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
        ], 403);
    }
    /**
     * Ошибка не найдено (404 Not Found).
     */
    protected function notFound(string $message = 'Not found'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
        ], 404);
    }
    /**
     * Ошибка сервера (500 Internal Server Error).
     */
    protected function serverError(string $message = 'Server error'): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
        ], 500);
    }
}
