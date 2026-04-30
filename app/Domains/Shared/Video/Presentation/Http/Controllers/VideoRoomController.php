<?php

declare(strict_types=1);

namespace Modules\Video\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Video\Application\Services\VideoRoomService;
use Modules\Video\Domain\DTOs\CreateRoomDTO;
use Modules\Video\Domain\DTOs\JoinRoomDTO;
use Modules\Video\Domain\Entities\VideoParticipant;
use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\Exceptions\VideoRoomException;
use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\RoomType;

final readonly class VideoRoomController
{
    public function __construct(
        private VideoRoomService $roomService,
    ) {
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:consultation,grooming_demo,masterclass,surgery,group_call',
            'host_id' => 'required|string',
            'pet_id' => 'sometimes|string',
            'scheduled_at' => 'sometimes|date',
            'participant_ids' => 'sometimes|array',
            'participant_ids.*' => 'string',
            'require_recording_consent' => 'sometimes|boolean',
            'metadata' => 'sometimes|array',
        ]);

        try {
            $dto = new CreateRoomDTO(
                type: RoomType::from($request->input('type')),
                hostId: $request->input('host_id'),
                petId: $request->input('pet_id'),
                scheduledAt: $request->input('scheduled_at')
                    ? new \DateTimeImmutable($request->input('scheduled_at'))
                    : null,
                participantIds: $request->input('participant_ids'),
                requireRecordingConsent: $request->input('require_recording_consent', false),
                metadata: $request->input('metadata'),
            );

            $room = $this->roomService->createRoom($dto);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $room->id,
                    'type' => $room->type->value,
                    'status' => $room->status->value,
                    'host_id' => $room->hostId,
                    'pet_id' => $room->petId,
                    'livekit_room_name' => $room->livekitRoomName,
                    'scheduled_at' => $room->scheduledAt?->format('Y-m-d H:i:s'),
                ],
            ], 201);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create video room',
            ], 500);
        }
    }

    public function start(string $roomId): JsonResponse
    {
        try {
            $room = $this->roomService->startRoom($roomId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $room->id,
                    'status' => $room->status->value,
                    'started_at' => $room->startedAt?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function end(Request $request, string $roomId): JsonResponse
    {
        try {
            $room = $this->roomService->endRoom(
                $roomId,
                $request->input('recording_url'),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $room->id,
                    'status' => $room->status->value,
                    'ended_at' => $room->endedAt?->format('Y-m-d H:i:s'),
                    'recording_url' => $room->recordingUrl,
                    'duration_seconds' => $room->getDuration(),
                ],
            ]);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function join(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|string',
            'user_id' => 'required|string',
            'role' => 'required|string|in:host,co_host,viewer',
            'display_name' => 'sometimes|string',
            'metadata' => 'sometimes|array',
        ]);

        try {
            $dto = new JoinRoomDTO(
                roomId: $request->input('room_id'),
                userId: $request->input('user_id'),
                role: ParticipantRole::from($request->input('role')),
                displayName: $request->input('display_name'),
                metadata: $request->input('metadata'),
            );

            $participant = $this->roomService->joinRoom($dto);

            $accessToken = $this->roomService->getParticipantAccessToken(
                $dto->roomId,
                $dto->userId,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'participant_id' => $participant->id,
                    'access_token' => $accessToken,
                    'livekit_participant_identity' => $participant->livekitParticipantIdentity,
                    'joined_at' => $participant->joinedAt?->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function leave(string $roomId, string $userId): JsonResponse
    {
        try {
            $participant = $this->roomService->leaveRoom($roomId, $userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'participant_id' => $participant->id,
                    'left_at' => $participant->leftAt?->format('Y-m-d H:i:s'),
                    'duration_seconds' => $participant->getDuration(),
                ],
            ]);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function giveConsent(string $roomId): JsonResponse
    {
        try {
            $room = $this->roomService->giveRecordingConsent($roomId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $room->id,
                    'recording_consent_given' => $room->recordingConsentGiven,
                ],
            ]);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function getAccessToken(string $roomId, string $userId): JsonResponse
    {
        try {
            $accessToken = $this->roomService->getParticipantAccessToken($roomId, $userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $accessToken,
                ],
            ]);
        } catch (VideoRoomException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(string $roomId): JsonResponse
    {
        $room = $this->roomService->getRoom($roomId);

        if ($room === null) {
            return response()->json([
                'success' => false,
                'error' => 'Room not found',
            ], 404);
        }

        $participants = $this->roomService->getRoomParticipants($roomId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $room->id,
                'type' => $room->type->value,
                'status' => $room->status->value,
                'host_id' => $room->hostId,
                'pet_id' => $room->petId,
                'recording_consent_given' => $room->recordingConsentGiven,
                'recording_url' => $room->recordingUrl,
                'scheduled_at' => $room->scheduledAt?->format('Y-m-d H:i:s'),
                'started_at' => $room->startedAt?->format('Y-m-d H:i:s'),
                'ended_at' => $room->endedAt?->format('Y-m-d H:i:s'),
                'duration_seconds' => $room->getDuration(),
                'participants' => array_map(fn (VideoParticipant $p) => [
                    'id' => $p->id,
                    'user_id' => $p->userId,
                    'role' => $p->role->value,
                    'status' => $p->status->value,
                    'joined_at' => $p->joinedAt?->format('Y-m-d H:i:s'),
                    'left_at' => $p->leftAt?->format('Y-m-d H:i:s'),
                ], $participants),
            ],
        ]);
    }

    public function getByHost(string $hostId): JsonResponse
    {
        $rooms = $this->roomService->getActiveRoomsByHost($hostId);

        return response()->json([
            'success' => true,
            'data' => array_map(fn (VideoRoom $room) => [
                'id' => $room->id,
                'type' => $room->type->value,
                'status' => $room->status->value,
                'pet_id' => $room->petId,
                'scheduled_at' => $room->scheduledAt?->format('Y-m-d H:i:s'),
            ], $rooms),
        ]);
    }
}
