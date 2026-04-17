<?php

declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\DTOs\LiveStreamSessionDto;
use App\Domains\Sports\Services\SportsLiveStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class LiveStreamController extends Controller
{
    public function __construct(
        private SportsLiveStreamService $service,
    ) {}

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trainer_id' => 'required|integer|exists:sports_trainers,id',
            'session_title' => 'required|string|max:255',
            'session_description' => 'nullable|string|max:2000',
            'scheduled_start' => 'required|date|after:now',
            'scheduled_end' => 'required|date|after:scheduled_start',
            'stream_type' => 'required|string|in:group,personal,workshop',
            'max_participants' => 'sometimes|integer|min:1|max:100',
            'tags' => 'sometimes|array',
            'business_group_id' => 'nullable|integer|exists:business_groups,id',
        ]);

        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 0;
        
        $dto = new LiveStreamSessionDto(
            userId: auth()->id(),
            tenantId: $tenantId,
            businessGroupId: $validated['business_group_id'] ?? null,
            trainerId: $validated['trainer_id'],
            sessionTitle: $validated['session_title'],
            sessionDescription: $validated['session_description'] ?? '',
            scheduledStart: $validated['scheduled_start'],
            scheduledEnd: $validated['scheduled_end'],
            streamType: $validated['stream_type'],
            maxParticipants: $validated['max_participants'] ?? 50,
            tags: $validated['tags'] ?? [],
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        $result = $this->service->createLiveStream($dto);

        return response()->json($result);
    }

    public function start(Request $request, int $streamId): JsonResponse
    {
        $this->authorize('start', $streamId);

        $result = $this->service->startLiveStream(
            streamId: $streamId,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function join(Request $request, int $streamId): JsonResponse
    {
        $result = $this->service->joinLiveStream(
            streamId: $streamId,
            userId: auth()->id(),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($result);
    }

    public function leave(Request $request, int $streamId): JsonResponse
    {
        $this->service->leaveLiveStream(
            streamId: $streamId,
            userId: auth()->id(),
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'success' => true,
            'message' => 'Left the stream successfully',
        ]);
    }

    public function end(Request $request, int $streamId): JsonResponse
    {
        $this->authorize('end', $streamId);

        $this->service->endLiveStream(
            streamId: $streamId,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'success' => true,
            'message' => 'Stream ended successfully',
        ]);
    }

    public function getActiveStreams(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'venue_id' => 'nullable|integer|exists:sports_gyms,id',
        ]);

        $streams = $this->service->getActiveStreams(
            venueId: $validated['venue_id'] ?? null,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json([
            'streams' => $streams,
        ]);
    }

    public function getRecording(Request $request, int $streamId): JsonResponse
    {
        $this->authorize('viewRecording', $streamId);

        $recording = $this->service->getStreamRecording(
            streamId: $streamId,
            correlationId: $request->header('X-Correlation-ID', Str::uuid()->toString()),
        );

        return response()->json($recording);
    }
}
