<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\PropertyViewing;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;

final readonly class RealEstateWebRTCService
{
    private const ROOM_TTL_SECONDS = 7200;
    private const MAX_PARTICIPANTS = 5;
    private const CALL_DURATION_LIMIT_SECONDS = 3600;
    private const PARTICIPANT_JOIN_TTL = 300;

    public function __construct(
        private FraudControlService $fraudControl,
        private AuditService $audit
    ) {}

    public function createVideoCallRoom(
        int $propertyId,
        int $userId,
        int $agentId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $userId,
            'create_webrtc_room',
            0,
            null,
            null,
            $correlationId
        );

        $roomId = $this->generateRoomId($propertyId, $userId, $agentId);
        $roomKey = $this->getRoomKey($roomId);

        $existingRoom = Redis::get($roomKey);
        if ($existingRoom !== null) {
            $roomData = json_decode($existingRoom, true);
            if ($roomData['status'] === 'active' && !$this->isRoomExpired($roomData)) {
                return [
                    'room_id' => $roomId,
                    'status' => 'existing',
                    'expires_at' => $roomData['expires_at'],
                    'participants' => $roomData['participants'],
                ];
            }
        }

        $roomData = [
            'room_id' => $roomId,
            'property_id' => $propertyId,
            'user_id' => $userId,
            'agent_id' => $agentId,
            'status' => 'active',
            'participants' => [],
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds(self::ROOM_TTL_SECONDS)->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Redis::setex($roomKey, self::ROOM_TTL_SECONDS, json_encode($roomData));

        $this->audit->record(
            'webrtc_room_created',
            'App\Domains\RealEstate\Models\Property',
            $propertyId,
            [],
            [
                'room_id' => $roomId,
                'user_id' => $userId,
                'agent_id' => $agentId,
            ],
            $correlationId
        );

        return [
            'room_id' => $roomId,
            'status' => 'created',
            'expires_at' => $roomData['expires_at'],
            'turn_server_url' => config('services.webrtc.turn_server_url'),
            'stun_server_url' => config('services.webrtc.stun_server_url'),
            'max_participants' => self::MAX_PARTICIPANTS,
            'duration_limit_seconds' => self::CALL_DURATION_LIMIT_SECONDS,
        ];
    }

    public function joinVideoCall(
        string $roomId,
        int $participantId,
        string $participantRole,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $participantId,
            'join_webrtc_call',
            0,
            null,
            null,
            $correlationId
        );

        $roomKey = $this->getRoomKey($roomId);
        $roomDataJson = Redis::get($roomKey);

        if ($roomDataJson === null) {
            throw new \DomainException('Video call room not found or expired');
        }

        $roomData = json_decode($roomDataJson, true);

        if ($roomData['status'] !== 'active') {
            throw new \DomainException('Video call room is not active');
        }

        if ($this->isRoomExpired($roomData)) {
            Redis::del($roomKey);
            throw new \DomainException('Video call room has expired');
        }

        if (count($roomData['participants']) >= self::MAX_PARTICIPANTS) {
            throw new \DomainException('Maximum participants limit reached');
        }

        $participantKey = $this->getParticipantKey($roomId, $participantId);
        $existingParticipant = Redis::get($participantKey);

        if ($existingParticipant !== null) {
            $participant = json_decode($existingParticipant, true);
            return [
                'room_id' => $roomId,
                'participant_id' => $participantId,
                'status' => 'already_joined',
                'joined_at' => $participant['joined_at'],
                'expires_at' => $participant['expires_at'],
            ];
        }

        $participant = [
            'participant_id' => $participantId,
            'role' => $participantRole,
            'joined_at' => now()->toIso8601String(),
            'expires_at' => now()->addSeconds(self::PARTICIPANT_JOIN_TTL)->toIso8601String(),
            'correlation_id' => $correlationId,
        ];

        Redis::setex($participantKey, self::PARTICIPANT_JOIN_TTL, json_encode($participant));

        $roomData['participants'][] = $participant;
        Redis::setex($roomKey, self::ROOM_TTL_SECONDS, json_encode($roomData));

        $this->audit->record(
            'webrtc_participant_joined',
            'App\Domains\RealEstate\Models\PropertyViewing',
            $participantId,
            [],
            [
                'room_id' => $roomId,
                'role' => $participantRole,
            ],
            $correlationId
        );

        return [
            'room_id' => $roomId,
            'participant_id' => $participantId,
            'status' => 'joined',
            'joined_at' => $participant['joined_at'],
            'expires_at' => $participant['expires_at'],
            'turn_server_url' => config('services.webrtc.turn_server_url'),
            'stun_server_url' => config('services.webrtc.stun_server_url'),
            'ice_servers' => $this->getIceServers(),
        ];
    }

    public function leaveVideoCall(
        string $roomId,
        int $participantId,
        string $correlationId
    ): void {
        $this->fraudControl->check(
            $participantId,
            'leave_webrtc_call',
            0,
            null,
            null,
            $correlationId
        );

        $participantKey = $this->getParticipantKey($roomId, $participantId);
        $participantJson = Redis::get($participantKey);

        if ($participantJson === null) {
            return;
        }

        $participant = json_decode($participantJson, true);
        Redis::del($participantKey);

        $roomKey = $this->getRoomKey($roomId);
        $roomDataJson = Redis::get($roomKey);

        if ($roomDataJson !== null) {
            $roomData = json_decode($roomDataJson, true);
            $roomData['participants'] = array_filter(
                $roomData['participants'],
                fn($p) => $p['participant_id'] !== $participantId
            );
            $roomData['participants'] = array_values($roomData['participants']);

            if (count($roomData['participants']) === 0) {
                Redis::del($roomKey);
                $roomData['status'] = 'ended';
                $roomData['ended_at'] = now()->toIso8601String();
            } else {
                Redis::setex($roomKey, self::ROOM_TTL_SECONDS, json_encode($roomData));
            }

            $this->audit->record(
                'webrtc_participant_left',
                'App\Domains\RealEstate\Models\PropertyViewing',
                $participantId,
                [],
                [
                    'room_id' => $roomId,
                    'role' => $participant['role'],
                    'remaining_participants' => count($roomData['participants']),
                ],
                $correlationId
            );
        }
    }

    public function endVideoCall(
        string $roomId,
        int $initiatorId,
        string $correlationId
    ): array {
        $this->fraudControl->check(
            $initiatorId,
            'end_webrtc_call',
            0,
            null,
            null,
            $correlationId
        );

        $roomKey = $this->getRoomKey($roomId);
        $roomDataJson = Redis::get($roomKey);

        if ($roomDataJson === null) {
            throw new \DomainException('Video call room not found');
        }

        $roomData = json_decode($roomDataJson, true);

        if ($roomData['user_id'] !== $initiatorId && $roomData['agent_id'] !== $initiatorId) {
            throw new \DomainException('Only call participants can end the call');
        }

        $roomData['status'] = 'ended';
        $roomData['ended_at'] = now()->toIso8601String();
        $roomData['ended_by'] = $initiatorId;
        $roomData['duration_seconds'] = Carbon::parse($roomData['created_at'])->diffInSeconds(now());

        Redis::del($roomKey);

        foreach ($roomData['participants'] as $participant) {
            $participantKey = $this->getParticipantKey($roomId, $participant['participant_id']);
            Redis::del($participantKey);
        }

        $this->audit->record(
            'webrtc_call_ended',
            'App\Domains\RealEstate\Models\PropertyViewing',
            $initiatorId,
            [],
            [
                'room_id' => $roomId,
                'duration_seconds' => $roomData['duration_seconds'],
                'participant_count' => count($roomData['participants']),
            ],
            $correlationId
        );

        return [
            'room_id' => $roomId,
            'status' => 'ended',
            'ended_at' => $roomData['ended_at'],
            'ended_by' => $initiatorId,
            'duration_seconds' => $roomData['duration_seconds'],
        ];
    }

    public function getCallStatus(
        string $roomId,
        string $correlationId
    ): array {
        $roomKey = $this->getRoomKey($roomId);
        $roomDataJson = Redis::get($roomKey);

        if ($roomDataJson === null) {
            return [
                'room_id' => $roomId,
                'status' => 'not_found',
            ];
        }

        $roomData = json_decode($roomDataJson, true);

        return [
            'room_id' => $roomId,
            'status' => $roomData['status'],
            'property_id' => $roomData['property_id'],
            'participant_count' => count($roomData['participants']),
            'participants' => $roomData['participants'],
            'created_at' => $roomData['created_at'],
            'expires_at' => $roomData['expires_at'],
            'is_expired' => $this->isRoomExpired($roomData),
            'duration_seconds' => isset($roomData['duration_seconds']) ? $roomData['duration_seconds'] : null,
        ];
    }

    public function generateViewingWebRTC(
        PropertyViewing $viewing,
        string $correlationId
    ): array {
        $this->fraudControl->check([
            'viewing_id' => $viewing->id,
            'action' => 'generate_viewing_webrtc'
        ], $correlationId);

        if ($viewing->agent_id === null) {
            throw new \DomainException('Cannot create video call: no agent assigned');
        }

        $roomResult = $this->createVideoCallRoom(
            $viewing->property_id,
            $viewing->user_id,
            $viewing->agent_id,
            $correlationId
        );

        $viewing->update([
            'webrtc_room_id' => $roomResult['room_id'],
            'webrtc_enabled' => true,
        ]);

        $this->audit->record(
            'viewing_webrtc_enabled',
            'App\Domains\RealEstate\Models\PropertyViewing',
            $viewing->id,
            [],
            [
                'property_id' => $viewing->property_id,
                'room_id' => $roomResult['room_id'],
            ],
            $correlationId
        );

        return array_merge($roomResult, [
            'viewing_id' => $viewing->id,
            'scheduled_at' => $viewing->scheduled_at->toIso8601String(),
            'agent_id' => $viewing->agent_id,
        ]);
    }

    private function generateRoomId(int $propertyId, int $userId, int $agentId): string
    {
        return 're_call_' . $propertyId . '_' . $userId . '_' . $agentId . '_' . Str::random(8);
    }

    private function getRoomKey(string $roomId): string
    {
        return "re:webrtc:room:{$roomId}";
    }

    private function getParticipantKey(string $roomId, int $participantId): string
    {
        return "re:webrtc:participant:{$roomId}:{$participantId}";
    }

    private function isRoomExpired(array $roomData): bool
    {
        return now()->isAfter(Carbon::parse($roomData['expires_at']));
    }

    private function getIceServers(): array
    {
        return [
            [
                'urls' => config('services.webrtc.stun_server_url', 'stun:stun.l.google.com:19302'),
            ],
            [
                'urls' => config('services.webrtc.turn_server_url'),
                'username' => config('services.webrtc.turn_username'),
                'credential' => config('services.webrtc.turn_credential'),
            ],
        ];
    }
}
