<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class BaseApiController extends Controller
{
    public function __construct(
        private readonly Request $request,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}


    /**
     * Получить correlation_id из запроса.
     */
    protected function getCorrelationId(): string
    {
        return $this->request->header('X-Correlation-ID', Str::uuid()->toString());
    }

    /**
     * Получить tenant_id из текущего юзера.
     */
    protected function getTenantId(): int
    {
        return $this->guard->user()?->tenant_id ?? filament()->getTenant()?->id ?? 1;
    }

    /**
     * Успешный ответ (200 OK).
     */
    protected function success(array $data = [], string $message = 'Success'): JsonResponse
    {
        return $this->response->json([
            'success' => true,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
            'data' => $data,
        ], 200);
    }

    /**
     * Ошибка валидации (422 Unprocessable Entity).
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
            'errors' => $errors,
        ], 422);
    }

    /**
     * Ошибка авторизации (403 Forbidden).
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
        ], 403);
    }

    /**
     * Ошибка не найдено (404 Not Found).
     */
    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
        ], 404);
    }

    /**
     * Ошибка сервера (500 Internal Server Error).
     */
    protected function serverError(string $message = 'Server error'): JsonResponse
    {
        return $this->response->json([
            'success' => false,
            'message' => $message,
            'correlation_id' => $this->getCorrelationId(),
        ], 500);
    }
}
