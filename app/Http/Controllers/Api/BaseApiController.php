<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

abstract class BaseApiController extends Controller
{
    protected function generateCorrelationId(): string
    {
        return Str::uuid()->toString();
    }

    protected function successResponse($data, string $message = 'Success', int $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'correlation_id' => request()->attributes->get('correlation_id', $this->generateCorrelationId()),
        ], $statusCode);
    }

    protected function errorResponse(string $message, int $statusCode = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'correlation_id' => request()->attributes->get('correlation_id', $this->generateCorrelationId()),
        ], $statusCode);
    }
}
