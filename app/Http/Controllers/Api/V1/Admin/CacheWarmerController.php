<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CacheWarmerRequest;
use App\Jobs\CacheWarmers\WarmUserTasteProfileJob;
use App\Jobs\CacheWarmers\WarmPopularProductsJob;
use Illuminate\Http\JsonResponse;

final class CacheWarmerController extends Controller
{
    public function warm(CacheWarmerRequest $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();

        if ($userId = $request->input('user_id')) {
            dispatch(new WarmUserTasteProfileJob($userId));
        }

        if ($vertical = $request->input('vertical')) {
            dispatch(new WarmPopularProductsJob($vertical));
        }

        return response()->json([
            'success' => true,
            'message' => 'Cache warming job queued',
            'correlation_id' => $correlationId,
        ], 202);
    }
}
