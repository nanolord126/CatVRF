<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Routing\ResponseFactory;
abstract class BaseApiV1Controller extends Controller
{
    protected string $apiVersion = 'v1';
    /**
     * Обработчик ошибок для try/catch
     */
    protected function errorResponse(\Throwable $e, string $correlationId, int $code = 500): ResponseFactory
    {
        \Illuminate\Support\Facades\Log::channel('audit')->error('Controller error', [
            'error' => $e->getMessage(),
            'code' => $code,
            'correlation_id' => $correlationId,
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'correlation_id' => $correlationId,
        ], $code);
    }
    /**
     * Create JSON response with metadata
     */
    protected function respondWithSuccess(
        mixed $data,
        string $message = 'Success',
        int $code = 200
    ): ResponseFactory {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'api_version' => $this->apiVersion,
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => request()->header('X-Correlation-ID'),
        ], $code);
    }
    protected function respondWithError(
        string $error,
        int $code = 400,
        array $details = []
    ): ResponseFactory {
        return response()->json([
            'success' => false,
            'error' => $error,
            'details' => $details,
            'api_version' => $this->apiVersion,
            'timestamp' => now()->toIso8601String(),
            'correlation_id' => request()->header('X-Correlation-ID'),
        ], $code);
    }
}
