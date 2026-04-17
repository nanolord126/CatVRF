<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Controllers;

use App\Domains\Beauty\DTOs\VideoCallDto;
use App\Domains\Beauty\Requests\VideoCallRequest;
use App\Domains\Beauty\Resources\VideoCallResource;
use App\Domains\Beauty\Services\VideoCallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class VideoCallController
{
    public function __construct(
        private VideoCallService $videoCallService,
    ) {}

    public function initiate(VideoCallRequest $request): JsonResponse
    {
        $dto = VideoCallDto::from($request);

        $result = $this->videoCallService->initiate($dto);

        return response()->json([
            'success' => true,
            'data' => new VideoCallResource($result),
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    public function end(Request $request): JsonResponse
    {
        $callId = $request->input('call_id');
        $durationSeconds = (int) $request->input('duration_seconds');
        $reason = $request->input('reason', 'user_ended');

        $result = $this->videoCallService->end($callId, $durationSeconds, $reason);

        return response()->json($result);
    }
}
