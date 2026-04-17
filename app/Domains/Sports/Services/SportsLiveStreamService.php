<?php

declare(strict_types=1);

namespace App\Domains\Sports\Services;

use App\Domains\Sports\DTOs\LiveStreamSessionDto;
use App\Domains\Sports\Events\LiveStreamStartedEvent;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final readonly class SportsLiveStreamService
{
    private const CACHE_TTL = 300;
    private const TOKEN_EXPIRY_MINUTES = 120;
    private const MAX_PARTICIPANTS_PER_STREAM = 100;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private DatabaseManager $db,
        private Cache $cache,
        private LoggerInterface $logger,
        private RedisConnection $redis,
    ) {}

    public function createLiveStream(LiveStreamSessionDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'live_stream_creation',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto) {
            $streamId = $this->db->table('sports_live_streams')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'user_id' => $dto->userId,
                'trainer_id' => $dto->trainerId,
                'session_title' => $dto->sessionTitle,
                'session_description' => $dto->sessionDescription,
                'scheduled_start' => $dto->scheduledStart,
                'scheduled_end' => $dto->scheduledEnd,
                'stream_type' => $dto->streamType,
                'max_participants' => min($dto->maxParticipants, self::MAX_PARTICIPANTS_PER_STREAM),
                'current_participants' => 0,
                'status' => 'scheduled',
                'webrtc_room' => null,
                'stream_token' => null,
                'tags' => json_encode($dto->tags),
                'correlation_id' => $dto->correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roomName = "sports_stream_{$streamId}";
            
            $this->db->table('sports_live_streams')
                ->where('id', $streamId)
                ->update(['webrtc_room' => $roomName]);

            $this->audit->log(
                action: 'live_stream_created',
                entityType: 'sports_live_stream',
                entityId: $streamId,
                metadata: [
                    'trainer_id' => $dto->trainerId,
                    'session_title' => $dto->sessionTitle,
                    'scheduled_start' => $dto->scheduledStart,
                    'max_participants' => $dto->maxParticipants,
                    'correlation_id' => $dto->correlationId,
                ]
            );

            $this->logger->info('Live stream created', [
                'stream_id' => $streamId,
                'trainer_id' => $dto->trainerId,
                'session_title' => $dto->sessionTitle,
                'correlation_id' => $dto->correlationId,
            ]);

            return [
                'stream_id' => $streamId,
                'room_name' => $roomName,
                'status' => 'scheduled',
                'scheduled_start' => $dto->scheduledStart,
                'scheduled_end' => $dto->scheduledEnd,
            ];
        });
    }

    public function startLiveStream(int $streamId, int $userId, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'live_stream_start',
            amount: 0,
            correlationId: $correlationId,
        );

        $stream = $this->db->table('sports_live_streams')->where('id', $streamId)->first();
        
        if ($stream === null) {
            throw new \RuntimeException('Stream not found.');
        }

        if ($stream->status !== 'scheduled') {
            throw new \RuntimeException('Stream is not in scheduled status.');
        }

        $token = Str::random(64);
        $expiresAt = now()->addMinutes(self::TOKEN_EXPIRY_MINUTES);

        $this->db->transaction(function () use ($streamId, $token, $expiresAt, $stream, $correlationId) {
            $this->db->table('sports_live_streams')
                ->where('id', $streamId)
                ->update([
                    'status' => 'live',
                    'stream_token' => $token,
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->redis->setex(
                "sports:webrtc:token:{$token}",
                $expiresAt->diffInSeconds(now()),
                json_encode([
                    'stream_id' => $streamId,
                    'trainer_id' => $stream->trainer_id,
                    'room_name' => $stream->webrtc_room,
                    'correlation_id' => $correlationId,
                ])
            );

            event(new LiveStreamStartedEvent(
                streamId: $streamId,
                trainerId: $stream->trainer_id,
                streamTitle: $stream->session_title,
                webrtcRoom: $stream->webrtc_room,
                correlationId: $correlationId,
            ));

            $this->audit->log(
                action: 'live_stream_started',
                entityType: 'sports_live_stream',
                entityId: $streamId,
                metadata: [
                    'trainer_id' => $stream->trainer_id,
                    'room_name' => $stream->webrtc_room,
                    'correlation_id' => $correlationId,
                ]
            );

            $this->logger->info('Live stream started', [
                'stream_id' => $streamId,
                'trainer_id' => $stream->trainer_id,
                'room_name' => $stream->webrtc_room,
                'correlation_id' => $correlationId,
            ]);
        });

        return [
            'stream_id' => $streamId,
            'token' => $token,
            'room_name' => $stream->webrtc_room,
            'webrtc_url' => config('services.webrtc.endpoint') . "/room/{$stream->webrtc_room}?token={$token}",
            'expires_at' => $expiresAt->toIso8601String(),
            'status' => 'live',
        ];
    }

    public function joinLiveStream(int $streamId, int $userId, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'live_stream_join',
            amount: 0,
            correlationId: $correlationId,
        );

        $stream = $this->db->table('sports_live_streams')->where('id', $streamId)->first();
        
        if ($stream === null) {
            throw new \RuntimeException('Stream not found.');
        }

        if ($stream->status !== 'live') {
            throw new \RuntimeException('Stream is not currently live.');
        }

        if ($stream->current_participants >= $stream->max_participants) {
            throw new \RuntimeException('Stream has reached maximum capacity.');
        }

        $participantKey = "sports:stream:{$streamId}:participants:{$userId}";
        if ($this->redis->exists($participantKey)) {
            throw new \RuntimeException('User is already participating in this stream.');
        }

        $participantToken = Str::random(64);
        
        $this->db->transaction(function () use ($streamId, $userId, $participantToken, $stream, $correlationId) {
            $this->db->table('sports_live_streams')
                ->where('id', $streamId)
                ->increment('current_participants');

            $this->db->table('sports_stream_participants')->insert([
                'stream_id' => $streamId,
                'user_id' => $userId,
                'joined_at' => now(),
                'participant_token' => $participantToken,
                'correlation_id' => $correlationId,
                'created_at' => now(),
            ]);

            $this->redis->setex($participantKey, 7200, json_encode([
                'user_id' => $userId,
                'joined_at' => now()->toIso8601String(),
                'correlation_id' => $correlationId,
            ]));

            $this->audit->log(
                action: 'live_stream_joined',
                entityType: 'sports_live_stream',
                entityId: $streamId,
                metadata: [
                    'user_id' => $userId,
                    'current_participants' => $stream->current_participants + 1,
                    'correlation_id' => $correlationId,
                ]
            );

            $this->logger->info('User joined live stream', [
                'stream_id' => $streamId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);
        });

        return [
            'stream_id' => $streamId,
            'participant_token' => $participantToken,
            'room_name' => $stream->webrtc_room,
            'webrtc_url' => config('services.webrtc.endpoint') . "/room/{$stream->webrtc_room}?token={$participantToken}",
            'current_participants' => $stream->current_participants + 1,
            'max_participants' => $stream->max_participants,
        ];
    }

    public function leaveLiveStream(int $streamId, int $userId, string $correlationId): void
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'live_stream_leave',
            amount: 0,
            correlationId: $correlationId,
        );

        $stream = $this->db->table('sports_live_streams')->where('id', $streamId)->first();
        
        if ($stream === null) {
            throw new \RuntimeException('Stream not found.');
        }

        $participantKey = "sports:stream:{$streamId}:participants:{$userId}";
        
        $this->db->transaction(function () use ($streamId, $userId, $participantKey, $correlationId) {
            $this->db->table('sports_live_streams')
                ->where('id', $streamId)
                ->where('current_participants', '>', 0)
                ->decrement('current_participants');

            $this->db->table('sports_stream_participants')
                ->where('stream_id', $streamId)
                ->where('user_id', $userId)
                ->update(['left_at' => now()]);

            $this->redis->del($participantKey);

            $this->audit->log(
                action: 'live_stream_left',
                entityType: 'sports_live_stream',
                entityId: $streamId,
                metadata: [
                    'user_id' => $userId,
                    'correlation_id' => $correlationId,
                ]
            );

            $this->logger->info('User left live stream', [
                'stream_id' => $streamId,
                'user_id' => $userId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function endLiveStream(int $streamId, int $userId, string $correlationId): void
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'live_stream_end',
            amount: 0,
            correlationId: $correlationId,
        );

        $stream = $this->db->table('sports_live_streams')->where('id', $streamId)->first();
        
        if ($stream === null) {
            throw new \RuntimeException('Stream not found.');
        }

        if ($stream->status !== 'live') {
            throw new \RuntimeException('Stream is not currently live.');
        }

        $this->db->transaction(function () use ($streamId, $stream, $correlationId) {
            $this->db->table('sports_live_streams')
                ->where('id', $streamId)
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($stream->stream_token !== null) {
                $this->redis->del("sports:webrtc:token:{$stream->stream_token}");
            }

            $this->audit->log(
                action: 'live_stream_ended',
                entityType: 'sports_live_stream',
                entityId: $streamId,
                metadata: [
                    'trainer_id' => $stream->trainer_id,
                    'duration_minutes' => now()->diffInMinutes($stream->started_at),
                    'total_participants' => $stream->current_participants,
                    'correlation_id' => $correlationId,
                ]
            );

            $this->logger->info('Live stream ended', [
                'stream_id' => $streamId,
                'trainer_id' => $stream->trainer_id,
                'total_participants' => $stream->current_participants,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    public function getActiveStreams(int $venueId = null, int $userId = 0, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'active_streams_list',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = "sports:active_streams:" . ($venueId ?? 'all');
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $query = $this->db->table('sports_live_streams')
            ->where('status', 'live');

        if ($venueId !== null) {
            $query->where('trainer_id', function ($q) use ($venueId) {
                $q->select('id')
                    ->from('sports_trainers')
                    ->where('gym_id', $venueId)
                    ->limit(1);
            });
        }

        $streams = $query->get()->map(function ($stream) {
            return [
                'stream_id' => $stream->id,
                'session_title' => $stream->session_title,
                'trainer_id' => $stream->trainer_id,
                'current_participants' => $stream->current_participants,
                'max_participants' => $stream->max_participants,
                'started_at' => $stream->started_at,
                'stream_type' => $stream->stream_type,
            ];
        })->toArray();

        $this->cache->put($cacheKey, json_encode($streams), self::CACHE_TTL);

        return $streams;
    }

    public function getStreamRecording(int $streamId, int $userId, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'stream_recording_access',
            amount: 0,
            correlationId: $correlationId,
        );

        $stream = $this->db->table('sports_live_streams')->where('id', $streamId)->first();
        
        if ($stream === null) {
            throw new \RuntimeException('Stream not found.');
        }

        if ($stream->status !== 'ended') {
            throw new \RuntimeException('Recording is only available for ended streams.');
        }

        $recordingUrl = $this->getRecordingUrl($streamId);

        return [
            'stream_id' => $streamId,
            'session_title' => $stream->session_title,
            'recording_url' => $recordingUrl,
            'duration_minutes' => $stream->ended_at ? now()->diffInMinutes($stream->started_at) : 0,
            'total_participants' => $stream->current_participants,
            'recorded_at' => $stream->ended_at,
        ];
    }

    private function getRecordingUrl(int $streamId): string
    {
        return config('services.webrtc.recording_url') . "/sports/stream/{$streamId}";
    }
}
