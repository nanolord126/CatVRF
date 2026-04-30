<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Psr\Log\LoggerInterface;

use App\Domains\Beauty\DTOs\VideoCallDto;
use App\Domains\Beauty\Events\VideoCallEndedEvent;
use App\Domains\Beauty\Models\Master;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

final readonly class VideoCallService
{
    private const CACHE_TTL = 600;

    private const MAX_DURATION = 1800;

    private const DEFAULT_DURATION = 300;

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,
        private readonly RedisConnection $redis,) {}

    public function initiate(VideoCallDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'beauty_video_call_initiate',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: request()->header('User-Agent'),
            correlationId: $dto->correlationId,
        );

        return $this->db->transaction(function () use ($dto) {
            $master = Master::findOrFail($dto->masterId);

            $callId = $this->generateCallId();
            $token = $this->generateWebRTCToken($dto->userId, $master->user_id, $callId);
            $roomName = "beauty_call_{$callId}";

            $duration = min($dto->durationMinutes ?? self::DEFAULT_DURATION, self::MAX_DURATION);

            $result = [
                'success' => true,
                'call_id' => $callId,
                'room_name' => $roomName,
                'token' => $token,
                'master_id' => $master->id,
                'master_name' => $master->full_name,
                'duration_seconds' => $duration * 60,
                'scheduled_for' => $dto->scheduledFor ?? CarbonImmutable::now()->toIso8601String(),
                'expires_at' => CarbonImmutable::now()->addSeconds($duration * 60 + 300)->toIso8601String(),
                'correlation_id' => $dto->correlationId,
            ];

            $this->storeCallSession($callId, $dto, $result);

            $this->log->channel('audit')->$this->logger->info('Video call initiated', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'master_id' => $dto->masterId,
                'call_id' => $callId,
                'tenant_id' => $dto->tenantId,
            ]);

            $this->eventDispatcher->dispatch(new VideoCallInitiatedEvent(
                userId: $dto->userId,
                masterId: $dto->masterId,
                callId: $callId,
                correlationId: $dto->correlationId,
            ));

            $this->audit->record(
                action: 'beauty_video_call_initiated',
                subjectType: Master::class,
                subjectId: $dto->masterId,
                oldValues: [],
                newValues: [
                    'call_id' => $callId,
                    'duration' => $duration,
                    'user_id' => $dto->userId,
                ],
                correlationId: $dto->correlationId,
            );

            return $result;
        });
    }

    public function end(string $callId, int $durationSeconds, string $reason): array
    {
        $session = $this->getCallSession($callId);

        if (! $session) {
            return [
                'success' => false,
                'error' => 'Call session not found',
            ];
        }

        $this->redis->del("beauty:video_call:{$callId}");

        $this->log->channel('audit')->$this->logger->info('Video call ended', [
            'call_id' => $callId,
            'duration_seconds' => $durationSeconds,
            'reason' => $reason,
        ]);

        $this->eventDispatcher->dispatch(new VideoCallEndedEvent(
            callId: $callId,
            userId: $session['user_id'],
            masterId: $session['master_id'],
            durationSeconds: $durationSeconds,
            reason: $reason,
            correlationId: $session['correlation_id'],
        ));

        return [
            'success' => true,
            'call_id' => $callId,
            'duration_seconds' => $durationSeconds,
        ];
    }

    private function generateCallId(): string
    {
        return 'vc_'.Str::random(16);
    }

    private function generateWebRTCToken(int $userId, int $masterId, string $callId): string
    {
        $payload = [
            'user_id' => $userId,
            'master_id' => $masterId,
            'call_id' => $callId,
            'exp' => CarbonImmutable::now()->addHours(1)->timestamp,
        ];

        return base64_encode(json_encode($payload));
    }

    private function storeCallSession(string $callId, VideoCallDto $dto, array $result): void
    {
        $session = [
            'call_id' => $callId,
            'user_id' => $dto->userId,
            'master_id' => $dto->masterId,
            'tenant_id' => $dto->tenantId,
            'correlation_id' => $dto->correlationId,
            'room_name' => $result['room_name'],
            'started_at' => CarbonImmutable::now()->toIso8601String(),
            'expires_at' => $result['expires_at'],
        ];

        $this->redis->setex("beauty:video_call:{$callId}", self::CACHE_TTL, json_encode($session));
    }

    private function getCallSession(string $callId): ?array
    {
        $session = $this->redis->get("beauty:video_call:{$callId}");

        return $session ? json_decode($session, true) : null;
    }
}
