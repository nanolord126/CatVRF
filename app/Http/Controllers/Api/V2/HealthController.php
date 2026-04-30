<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use Carbon\CarbonImmutable;

use App\Services\Api\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HealthController
{
    public function __construct(
        private readonly ApiResponseService $apiResponse
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->apiResponse->success([
            'status' => 'healthy',
            'version' => '2.0.0',
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
            'api_version' => 'v2',
        ], 'API v2 is operational');
    }
}
