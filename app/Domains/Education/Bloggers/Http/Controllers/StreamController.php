<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StreamController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly StreamService $streamService,
        ) {}

        /**
         * Get all active streams for current tenant
         */
        public function index(Request $request): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();

                $streams = Stream::where('tenant_id', tenant()->id)
                    ->where('status', 'live')
                    ->with(['blogger', 'statistics'])
                    ->orderByDesc('viewer_count')
                    ->paginate(20);

                Log::channel('audit')->info('Get active streams', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => tenant()->id,
                    'count' => $streams->total(),
                ]);

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => $streams->items(),
                    'pagination' => [
                        'total' => $streams->total(),
                        'per_page' => $streams->perPage(),
                        'current_page' => $streams->currentPage(),
                        'last_page' => $streams->lastPage(),
                    ],
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Get streams failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'message' => 'Failed to fetch streams',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        /**
         * Get blogger's stream schedule
         */
        public function getBloggerStreams(Request $request): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $blogerId = auth()->id();

                $streams = Stream::where('tenant_id', tenant()->id)
                    ->where('blogger_id', $blogerId)
                    ->with(['products', 'statistics'])
                    ->orderByDesc('created_at')
                    ->paginate(50);

                Log::channel('audit')->info('Get blogger streams', [
                    'correlation_id' => $correlationId,
                    'blogger_id' => $blogerId,
                    'count' => $streams->total(),
                ]);

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => $streams->items(),
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Get blogger streams failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Failed to fetch streams',
                ], 500);
            }
        }

        /**
         * Create new stream (scheduled)
         */
        public function store(CreateStreamRequest $request): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $blogerId = auth()->id();

                $stream = $this->streamService->createStream(
                    blogerId: $blogerId,
                    title: $request->string('title')->value(),
                    description: $request->string('description')->value(),
                    scheduledAt: $request->dateTime('scheduled_at'),
                    tags: $request->array('tags', []),
                    correlationId: $correlationId,
                );

                Log::channel('audit')->info('Stream created', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'blogger_id' => $blogerId,
                    'scheduled_at' => $stream->scheduled_at,
                ]);

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                    'broadcast_key' => $stream->broadcast_key,
                    'room_id' => $stream->room_id,
                ], 201);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Create stream failed', [
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'message' => 'Failed to create stream',
                    'error' => $e->getMessage(),
                ], 400);
            }
        }

        /**
         * Get stream details
         */
        public function show(string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();

                $stream = Stream::where('room_id', $roomId)
                    ->where('tenant_id', tenant()->id)
                    ->with(['blogger', 'products', 'statistics'])
                    ->firstOrFail();

                // Increment view count (if viewer is not broadcaster)
                if (auth()->check() && auth()->id() !== $stream->blogger_id) {
                    $stream->increment('view_count');
                }

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Get stream details failed', [
                    'room_id' => $roomId,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Stream not found',
                ], 404);
            }
        }

        /**
         * Start stream (transition from scheduled to live)
         */
        public function start(string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $blogerId = auth()->id();

                $stream = Stream::where('room_id', $roomId)
                    ->where('blogger_id', $blogerId)
                    ->firstOrFail();

                $stream = $this->streamService->startStream(
                    streamId: (int) $stream->id,
                    correlationId: $correlationId,
                );

                Log::channel('audit')->info('Stream started', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'blogger_id' => $blogerId,
                ]);

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Start stream failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Failed to start stream',
                    'error' => $e->getMessage(),
                ], 400);
            }
        }

        /**
         * End stream (transition to ended)
         */
        public function end(string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();
                $blogerId = auth()->id();

                $stream = Stream::where('room_id', $roomId)
                    ->where('blogger_id', $blogerId)
                    ->firstOrFail();

                $stream = $this->streamService->endStream(
                    streamId: (int) $stream->id,
                    correlationId: $correlationId,
                );

                Log::channel('audit')->info('Stream ended', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'duration_minutes' => $stream->duration_minutes,
                    'total_revenue' => $stream->total_revenue,
                ]);

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('End stream failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Failed to end stream',
                ], 400);
            }
        }

        /**
         * Update viewer count (called every 5 seconds from frontend)
         */
        public function updateViewers(Request $request, string $roomId): JsonResponse
        {
            try {
                $request->validate([
                    'viewer_count' => 'required|integer|min:0|max:10000',
                ]);

                $stream = Stream::where('room_id', $roomId)
                    ->where('tenant_id', tenant()->id)
                    ->firstOrFail();

                $this->streamService->updateViewerCount(
                    streamId: (int) $stream->id,
                    viewerCount: $request->integer('viewer_count'),
                );

                return response()->json([
                    'success' => true,
                    'viewer_count' => $stream->fresh()->viewer_count,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to update viewers',
                ], 400);
            }
        }

        /**
         * Get stream statistics
         */
        public function getStatistics(string $roomId): JsonResponse
        {
            try {
                $correlationId = (string) Str::uuid();

                $stream = Stream::where('room_id', $roomId)
                    ->where('tenant_id', tenant()->id)
                    ->with('statistics')
                    ->firstOrFail();

                $stats = $stream->statistics;

                if (!$stats) {
                    return response()->json([
                        'message' => 'Statistics not found',
                    ], 404);
                }

                return response()->json([
                    'correlation_id' => $correlationId,
                    'data' => [
                        'unique_viewers' => $stats->unique_viewers,
                        'total_messages' => $stats->total_messages,
                        'total_gifts' => $stats->total_gifts,
                        'engagement_rate' => $stats->engagement_rate,
                        'viewer_countries' => $stats->viewer_countries,
                        'traffic_sources' => $stats->traffic_sources,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::channel('audit')->error('Get stream statistics failed', [
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Failed to fetch statistics',
                ], 500);
            }
        }
}
