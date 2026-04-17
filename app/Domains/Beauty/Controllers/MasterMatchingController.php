<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Controllers;

use App\Domains\Beauty\DTOs\MasterMatchingByPhotoDto;
use App\Domains\Beauty\Requests\MatchMastersByPhotoRequest;
use App\Domains\Beauty\Resources\MasterMatchResource;
use App\Domains\Beauty\Services\MasterMatchingByPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Redis\Connections\Connection;

final class MasterMatchingController
{
    public function __construct(
        private MasterMatchingByPhotoService $matchingService,
        private readonly Connection $redis,
    ) {}

    public function matchByPhoto(MatchMastersByPhotoRequest $request): JsonResponse
    {
        $dto = MasterMatchingByPhotoDto::from($request);

        $result = $this->matchingService->match($dto);

        return response()->json([
            'success' => true,
            'data' => [
                'analysis' => $result['analysis'],
                'matched_masters' => MasterMatchResource::collection($result['matched_masters']),
                'total_matches' => $result['total_matches'],
            ],
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    public function getMatchHistory(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id');
        $key = "beauty:user_search_history:{$userId}";
        $history = $this->redis->lrange($key, 0, 9);

        $parsedHistory = array_map(fn($item) => json_decode($item, true), $history);

        return response()->json([
            'success' => true,
            'data' => $parsedHistory,
        ]);
    }
}
