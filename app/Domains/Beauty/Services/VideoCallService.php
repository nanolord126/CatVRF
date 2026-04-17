<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\DTOs\VideoCallDto;
use App\Domains\Beauty\Events\VideoCallEndedEvent;
use App\Domains\Beauty\Models\Master;
use App\Services\AuditService;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

final readonly class VideoCallService
{
    private const CACHE_TTL = 600;
    private const MAX_DURATION = 1800;
    private const DEFAULT_DURATION = 300;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
    ) {}

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

        return DB::transaction(function () use ($dto) {
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
                'scheduled_for' => $dto->scheduledFor ?? now()->toIso8601String(),
                'expires_at' => now()->addSeconds($duration * 60 + 300)->toIso8601String(),
                'correlation_id' => $dto->correlationId,
            ];

            $this->storeCallSession($callId, $dto, $result);

            Log::channel('audit')->info('Video call initiated', [
                'correlation_id' => $dto->correlationId,
                'user_id' => $dto->userId,
                'master_id' => $dto->masterId,
                'call_id' => $callId,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new VideoCallInitiatedEvent(
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

        if (!$session) {
            return [
                'success' => false,
                'error' => 'Call session not found',
            ];
        }

        Redis::del("beauty:video_call:{$callId}");

        Log::channel('audit')->info('Video call ended', [
            'call_id' => $callId,
            'duration_seconds' => $durationSeconds,
            'reason' => $reason,
        ]);

        event(new VideoCallEndedEvent(
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
        return 'vc_' . Str::random(16);
    }

    private function generateWebRTCToken(int $userId, int $masterId, string $callId): string
    {
        $payload = [
            'user_id' => $userId,
            'master_id' => $masterId,
            'call_id' => $callId,
            'exp' => now()->addHours(1)->timestamp,
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
            'started_at' => now()->toIso8601String(),
            'expires_at' => $result['expires_at'],
        ];

        Redis::setex("beauty:video_call:{$callId}", self::CACHE_TTL, json_encode($session));
    }

    private function getCallSession(string $callId): ?array
    {
        $session = Redis::get("beauty:video_call:{$callId}");
        return $session ? json_decode($session, true) : null;
    }
}
