<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class StreamController extends Controller
{

    public function __construct(
            private readonly StreamService $streamService, private readonly LoggerInterface $logger) {}

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

                $this->logger->info('Get active streams', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => tenant()->id,
                    'count' => $streams->total(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $streams->items(),
                    'pagination' => [
                        'total' => $streams->total(),
                        'per_page' => $streams->perPage(),
                        'current_page' => $streams->currentPage(),
                        'last_page' => $streams->lastPage(),
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Get streams failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $blogerId = $request->user()?->id;

                $streams = Stream::where('tenant_id', tenant()->id)
                    ->where('blogger_id', $blogerId)
                    ->with(['products', 'statistics'])
                    ->orderByDesc('created_at')
                    ->paginate(50);

                $this->logger->info('Get blogger streams', [
                    'correlation_id' => $correlationId,
                    'blogger_id' => $blogerId,
                    'count' => $streams->total(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $streams->items(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Get blogger streams failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $blogerId = $request->user()?->id;

                $stream = $this->streamService->createStream(
                    blogerId: $blogerId,
                    title: $request->string('title')->value(),
                    description: $request->string('description')->value(),
                    scheduledAt: $request->dateTime('scheduled_at'),
                    tags: $request->array('tags', []),
                    correlationId: $correlationId,
                );

                $this->logger->info('Stream created', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'blogger_id' => $blogerId,
                    'scheduled_at' => $stream->scheduled_at,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                    'broadcast_key' => $stream->broadcast_key,
                    'room_id' => $stream->room_id,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Create stream failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user()?->id,
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                if ($request->user() !== null && $request->user()?->id !== $stream->blogger_id) {
                    $stream->increment('view_count');
                }

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Get stream details failed', [
                    'room_id' => $roomId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $blogerId = $request->user()?->id;

                $stream = Stream::where('room_id', $roomId)
                    ->where('blogger_id', $blogerId)
                    ->firstOrFail();

                $stream = $this->streamService->startStream(
                    streamId: (int) $stream->id,
                    correlationId: $correlationId,
                );

                $this->logger->info('Stream started', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'blogger_id' => $blogerId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Start stream failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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
                $blogerId = $request->user()?->id;

                $stream = Stream::where('room_id', $roomId)
                    ->where('blogger_id', $blogerId)
                    ->firstOrFail();

                $stream = $this->streamService->endStream(
                    streamId: (int) $stream->id,
                    correlationId: $correlationId,
                );

                $this->logger->info('Stream ended', [
                    'correlation_id' => $correlationId,
                    'stream_id' => $stream->id,
                    'duration_minutes' => $stream->duration_minutes,
                    'total_revenue' => $stream->total_revenue,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'correlation_id' => $correlationId,
                    'data' => $stream,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('End stream failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'viewer_count' => $stream->fresh()->viewer_count,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
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
                    return new \Illuminate\Http\JsonResponse([
                        'message' => 'Statistics not found',
                    ], 404);
                }

                return new \Illuminate\Http\JsonResponse([
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
            } catch (\Throwable $e) {
                $this->logger->error('Get stream statistics failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'message' => 'Failed to fetch statistics',
                ], 500);
            }
        }
}
