<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Log\LogManager;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;

final readonly class ApiResponseService
{
    use WithAuditLogging;

    public function __construct(
        private readonly LogManager $log,
        private readonly AuditService $audit,
    ) {}

    /**
     * Standard success response
     */
    public function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Success response with pagination
     */
    public function successPaginated(
        LengthAwarePaginator $paginator,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Error response
     */
    public function error(
        string $message = 'Error',
        string $errorCode = 'ERROR',
        int $statusCode = 400,
        ?array $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'error' => $errorCode,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->log->warning('API error response', [
            'error_code' => $errorCode,
            'message' => $message,
            'status_code' => $statusCode,
            'errors' => $errors,
        ]);

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Validation error response
     */
    public function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error(
            message: $message,
            errorCode: 'VALIDATION_ERROR',
            statusCode: 422,
            errors: $errors
        );
    }

    /**
     * Not found response
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error(
            message: $message,
            errorCode: 'NOT_FOUND',
            statusCode: 404
        );
    }

    /**
     * Unauthorized response
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error(
            message: $message,
            errorCode: 'UNAUTHORIZED',
            statusCode: 401
        );
    }

    /**
     * Forbidden response
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error(
            message: $message,
            errorCode: 'FORBIDDEN',
            statusCode: 403
        );
    }

    /**
     * Rate limit exceeded response
     */
    public function rateLimitExceeded(int $retryAfter, string $message = 'Rate limit exceeded'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => 'RATE_LIMIT_EXCEEDED',
            'message' => $message,
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', (string) $retryAfter);
    }

    /**
     * Server error response
     */
    public function serverError(string $message = 'Internal server error', ?\Throwable $exception = null): JsonResponse
    {
        if ($exception !== null) {
            $this->log->error('API server error', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return $this->error(
            message: $message,
            errorCode: 'SERVER_ERROR',
            statusCode: 500
        );
    }

    /**
     * Created response
     */
    public function created(mixed $data = null, string $message = 'Resource created'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * No content response
     */
    public function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    /**
     * Accepted response (for async operations)
     */
    public function accepted(mixed $data = null, string $message = 'Request accepted'): JsonResponse
    {
        return $this->success($data, $message, 202);
    }
}
