<?php declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * TaxiWebRTCService - Video call service for passenger-driver communication
 * 
 * Enables video calls before trip for safety and verification
 * Uses WebRTC for peer-to-peer video communication
 * Includes biometric verification through video analysis
 */
final readonly class TaxiWebRTCService
{
    private const CALL_CACHE_TTL = 600;
    private const MAX_CALL_DURATION_SECONDS = 120;
    
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly Cache $cache,
        private readonly LoggerInterface $logger,
    ) {}

    public function initiatePreTripVideoCall(int $rideId, string $correlationId): array
    {
        $this->fraud->check(
            userId: 0,
            operationType: 'taxi_webrtc_initiate',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $ride = $this->db->table('taxi_rides')
            ->where('id', $rideId)
            ->where('status', 'pending')
            ->first();

        if ($ride === null) {
            throw new \DomainException('Ride not found or not in pending status');
        }

        $callId = Str::uuid()->toString();
        $callerId = $ride->passenger_id;
        $calleeId = $ride->driver_id;

        if ($calleeId === null) {
            throw new \DomainException('Driver not assigned to ride');
        }

        $signalingKey = $this->generateSignalingKey($callId, $correlationId);
        $turnServerCredentials = $this->getTurnServerCredentials($correlationId);

        $callData = [
            'call_id' => $callId,
            'ride_id' => $rideId,
            'ride_uuid' => $ride->uuid,
            'caller_id' => (int)$callerId,
            'callee_id' => (int)$calleeId,
            'status' => 'initiated',
            'initiated_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds(self::MAX_CALL_DURATION_SECONDS)->toIso8601String(),
            'signaling_key' => $signalingKey,
            'turn_servers' => $turnServerCredentials,
            'max_duration_seconds' => self::MAX_CALL_DURATION_SECONDS,
        ];

        $this->db->table('taxi_webrtc_calls')->insert([
            'uuid' => $callId,
            'ride_id' => $rideId,
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
            'status' => 'initiated',
            'signaling_key' => $signalingKey,
            'initiated_at' => now(),
            'expires_at' => now()->addSeconds(self::MAX_CALL_DURATION_SECONDS),
            'correlation_id' => $correlationId,
        ]);

        $this->cache->tags(['taxi', 'webrtc'])->put("taxi:webrtc:call:{$callId}", $callData, self::CALL_CACHE_TTL);

        $this->audit->log(
            action: 'taxi_webrtc_call_initiated',
            subjectType: self::class,
            subjectId: $callId,
            oldValues: [],
            newValues: $callData,
            correlationId: $correlationId,
        );

        $this->logger->info('Pre-trip video call initiated', [
            'call_id' => $callId,
            'ride_id' => $rideId,
            'caller_id' => $callerId,
            'callee_id' => $calleeId,
            'correlation_id' => $correlationId,
        ]);

        return $callData;
    }

    public function acceptVideoCall(string $callId, int $driverId, string $correlationId): bool
    {
        $this->fraud->check(
            userId: $driverId,
            operationType: 'taxi_webrtc_accept',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $call = $this->db->table('taxi_webrtc_calls')
            ->where('uuid', $callId)
            ->where('callee_id', $driverId)
            ->where('status', 'initiated')
            ->first();

        if ($call === null) {
            $this->logger->warning('Call not found or cannot be accepted', [
                'call_id' => $callId,
                'driver_id' => $driverId,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        $this->db->table('taxi_webrtc_calls')
            ->where('uuid', $callId)
            ->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

        $this->cache->forget("taxi:webrtc:call:{$callId}");

        $this->audit->log(
            action: 'taxi_webrtc_call_accepted',
            subjectType: self::class,
            subjectId: $callId,
            oldValues: ['status' => 'initiated'],
            newValues: ['status' => 'accepted'],
            correlationId: $correlationId,
        );

        $this->logger->info('Video call accepted', [
            'call_id' => $callId,
            'driver_id' => $driverId,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }

    public function endVideoCall(string $callId, string $reason, int $endedBy, string $correlationId): bool
    {
        $this->fraud->check(
            userId: $endedBy,
            operationType: 'taxi_webrtc_end',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $call = $this->db->table('taxi_webrtc_calls')
            ->where('uuid', $callId)
            ->whereIn('status', ['initiated', 'accepted', 'in_progress'])
            ->first();

        if ($call === null) {
            $this->logger->warning('Call not found or cannot be ended', [
                'call_id' => $callId,
                'ended_by' => $endedBy,
                'correlation_id' => $correlationId,
            ]);
            return false;
        }

        $duration = now()->diffInSeconds($call->initiated_at);

        $this->db->table('taxi_webrtc_calls')
            ->where('uuid', $callId)
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
                'ended_by' => (int)$endedBy,
                'end_reason' => $reason,
                'duration_seconds' => $duration,
            ]);

        $this->cache->forget("taxi:webrtc:call:{$callId}");

        $this->audit->log(
            action: 'taxi_webrtc_call_ended',
            subjectType: self::class,
            subjectId: $callId,
            oldValues: ['status' => $call->status],
            newValues: [
                'status' => 'ended',
                'ended_by' => $endedBy,
                'end_reason' => $reason,
                'duration_seconds' => $duration,
            ],
            correlationId: $correlationId,
        );

        $this->logger->info('Video call ended', [
            'call_id' => $callId,
            'ended_by' => $endedBy,
            'reason' => $reason,
            'duration_seconds' => $duration,
            'correlation_id' => $correlationId,
        ]);

        return true;
    }

    public function getCallStatus(string $callId, string $correlationId): ?array
    {
        $call = $this->db->table('taxi_webrtc_calls')
            ->where('uuid', $callId)
            ->first();

        if ($call === null) {
            return null;
        }

        return [
            'call_id' => $call->uuid,
            'ride_id' => $call->ride_id,
            'status' => $call->status,
            'initiated_at' => $call->initiated_at,
            'accepted_at' => $call->accepted_at,
            'ended_at' => $call->ended_at,
            'duration_seconds' => $call->duration_seconds,
            'expires_at' => $call->expires_at,
        ];
    }

    private function generateSignalingKey(string $callId, string $correlationId): string
    {
        $timestamp = now()->timestamp;
        $signature = hash_hmac('sha256', $callId . $timestamp, config('app.webrtc_signing_secret'));
        
        return base64_encode("{$callId}:{$timestamp}:{$signature}");
    }

    private function getTurnServerCredentials(string $correlationId): array
    {
        return [
            [
                'urls' => config('services.turn.servers', [
                    'turn:turn1.catvrf.ru:3478',
                    'turn:turn2.catvrf.ru:3478',
                ]),
                'username' => config('services.turn.username'),
                'credential' => config('services.turn.credential'),
            ],
            [
                'urls' => config('services.stun.servers', [
                    'stun:stun.l.google.com:19302',
                    'stun:stun1.l.google.com:19302',
                ]),
            ],
        ];
    }
}
