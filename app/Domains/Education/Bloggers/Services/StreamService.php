<?php declare(strict_types=1);

namespace App\Domains\Education\Bloggers\Services;

use Carbon\Carbon;



use Illuminate\Contracts\Auth\Guard;
use Illuminate\Cache\RateLimiter;
use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final readonly class StreamService
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly RateLimiterService $rateLimiter,
        private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly ConfigRepository $config, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        /**
         * Создать запланированный стрим
         */
        public function createStream(
            int $bloggerId,
            string $title,
            ?string $description = null,
            ?\DateTime $scheduledAt = null,
            array $settings = [],
            string $correlationId = '',
        ): Stream {
            $correlationId = $correlationId ?: (string) Str::uuid();

            // Rate limiting
            if (! $this->rateLimiter->allow('stream:create:' . $bloggerId, $this->config->get('bloggers.rate_limit.create_stream'))) {
                throw new \RuntimeException('Rate limit exceeded for creating streams');
            }

            // Fraud check
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'stream_create', amount: 0, correlationId: $correlationId ?? '');

            return $this->db->transaction(function () use ($bloggerId, $title, $description, $scheduledAt, $settings, $correlationId) {
                // Генерируем уникальный room_id и broadcast_key
                $roomId = 'stream_' . Str::random(16);
                $broadcastKey = Str::random(32);

                $stream = Stream::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'blogger_id' => $bloggerId,
                    'business_group_id' => filament()?->getTenant()?->active_business_group?->id,
                    'title' => $title,
                    'description' => $description,
                    'room_id' => $roomId,
                    'broadcast_key' => $broadcastKey,
                    'status' => 'scheduled',
                    'scheduled_at' => $scheduledAt,
                    'correlation_id' => $correlationId,
                    'record_stream' => $settings['record_stream'] ?? true,
                    'allow_chat' => $settings['allow_chat'] ?? true,
                    'allow_gifts' => $settings['allow_gifts'] ?? true,
                    'allow_commerce' => $settings['allow_commerce'] ?? true,
                ]);

                // Create statistics entry
                StreamStatistics::create([
                    'uuid' => (string) Str::uuid(),
                    'tenant_id' => tenant()->id,
                    'stream_id' => $stream->id,
                    'correlation_id' => $correlationId,
                ]);

                // Audit log
                $this->logger->info('Stream created', [
                    'stream_id' => $stream->id,
                    'blogger_id' => $bloggerId,
                    'title' => $title,
                    'correlation_id' => $correlationId,
                ]);

                event(new StreamCreated($stream));

                return $stream;
            });
        }

        /**
         * Запустить стрим (перевести из scheduled в live)
         */
        public function startStream(int $streamId, string $correlationId = ''): Stream
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            $stream = Stream::findOrFail($streamId);

            if ($stream->status !== 'scheduled') {
                throw new \RuntimeException('Only scheduled streams can be started');
            }

            return $this->db->transaction(function () use ($stream, $correlationId) {
                $stream->update([
                    'status' => 'live',
                    'started_at' => Carbon::now(),
                    'correlation_id' => $correlationId,
                ]);

                // Audit log
                $this->logger->info('Stream started', [
                    'stream_id' => $stream->id,
                    'blogger_id' => $stream->blogger_id,
                    'correlation_id' => $correlationId,
                ]);

                event(new StreamStarted($stream));

                return $stream;
            });
        }

        /**
         * Завершить стрим
         */
        public function endStream(int $streamId, string $correlationId = ''): Stream
        {
            $correlationId = $correlationId ?: (string) Str::uuid();

            $stream = Stream::findOrFail($streamId);

            if ($stream->status !== 'live') {
                throw new \RuntimeException('Only live streams can be ended');
            }

            return $this->db->transaction(function () use ($stream, $correlationId) {
                $durationSeconds = $stream->started_at ? Carbon::now()->diffInSeconds($stream->started_at) : 0;

                $stream->update([
                    'status' => 'ended',
                    'ended_at' => Carbon::now(),
                    'duration_seconds' => $durationSeconds,
                    'correlation_id' => $correlationId,
                ]);

                // Audit log
                $this->logger->info('Stream ended', [
                    'stream_id' => $stream->id,
                    'duration_seconds' => $durationSeconds,
                    'peak_viewers' => $stream->peak_viewers,
                    'correlation_id' => $correlationId,
                ]);

                event(new StreamEnded($stream));

                return $stream;
            });
        }

        /**
         * Обновить счётчик зрителей
         */
        public function updateViewerCount(int $streamId, int $viewerCount): void
        {
            $stream = Stream::find($streamId);
            if (! $stream) {
                return;
            }

            $stream->update([
                'viewer_count' => $viewerCount,
                'peak_viewers' => max($stream->peak_viewers, $viewerCount),
            ]);
        }

        /**
         * Получить активные стримы
         */
        public function getActiveStreams(): \Illuminate\Database\Eloquent\Collection
        {
            return Stream::where('status', 'live')
                ->with(['blogger', 'pinnedProducts', 'statistics'])
                ->orderByDesc('viewer_count')
                ->get();
        }

        /**
         * Получить стримы блогера
         */
        public function getBloggerStreams(int $bloggerId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
        {
            $query = Stream::where('blogger_id', $bloggerId);

            if ($status) {
                $query->where('status', $status);
            }

            return $query->orderByDesc('created_at')->get();
        }
}
