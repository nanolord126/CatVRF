<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\Video\VideoRoomService;
use App\Services\Video\RecordingService;
use App\Models\Video\VideoRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class VideoController
{
    public function __construct(
        private readonly VideoRoomService $videoRoomService,
        private readonly RecordingService $recordingService
    ) {
    }

    /**
     * Create video room
     */
    public function createRoom(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:consultation,grooming_demo,masterclass,surgery,group_call',
            'host_id' => 'required|uuid',
            'host_type' => 'required|string',
            'pet_id' => 'nullable|uuid',
            'pet_type' => 'nullable|string',
            'room_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'recording_enabled' => 'boolean',
            'is_public' => 'boolean',
            'max_participants' => 'integer|min:2|max:100',
        ]);

        try {
            $room = $this->videoRoomService->createRoom($request->all());

            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'type' => $room->type,
                    'status' => $room->status,
                    'room_name' => $room->room_name,
                    'scheduled_at' => $room->scheduled_at?->toIso8601String(),
                    'access_token' => $this->videoRoomService->generateRoomAccessToken($room),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start video room
     */
    public function startRoom(Request $request, string $roomId): JsonResponse
    {
        try {
            $room = $this->videoRoomService->getRoom($roomId);
            $room = $this->videoRoomService->startRoom($room);

            broadcast(new \App\Events\Video\VideoRoomStarted($room));

            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'status' => $room->status,
                    'started_at' => $room->started_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * End video room
     */
    public function endRoom(Request $request, string $roomId): JsonResponse
    {
        try {
            $room = $this->videoRoomService->getRoom($roomId);
            $room = $this->videoRoomService->endRoom($room);

            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'status' => $room->status,
                    'ended_at' => $room->ended_at->toIso8601String(),
                    'duration_seconds' => $room->duration_seconds,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Join video room
     */
    public function joinRoom(Request $request, string $roomId): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'user_type' => 'required|string',
        ]);

        try {
            $room = $this->videoRoomService->getRoom($roomId);
            $participant = $this->videoRoomService->joinRoom($room, $request->user_id, $request->user_type);

            // Generate LiveKit token
            $livekitToken = $this->videoRoomService->generateLiveKitToken($room, $participant);

            broadcast(new \App\Events\Video\ParticipantJoined($participant));

            return response()->json([
                'success' => true,
                'participant' => [
                    'id' => $participant->id,
                    'role' => $participant->role,
                    'livekit_token' => $livekitToken,
                    'livekit_room_name' => $room->livekit_room_name,
                    'livekit_participant_identity' => $participant->livekit_participant_identity,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Grant recording consent
     */
    public function grantRecordingConsent(Request $request, string $roomId): JsonResponse
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        try {
            $room = $this->videoRoomService->getRoom($roomId);
            $this->videoRoomService->grantRecordingConsent($room, $request->signature);

            return response()->json([
                'success' => true,
                'message' => 'Recording consent granted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get room details
     */
    public function getRoom(Request $request, string $roomId): JsonResponse
    {
        try {
            $room = $this->videoRoomService->getRoom($roomId);

            return response()->json([
                'success' => true,
                'room' => [
                    'id' => $room->id,
                    'type' => $room->type,
                    'status' => $room->status,
                    'room_name' => $room->room_name,
                    'description' => $room->description,
                    'scheduled_at' => $room->scheduled_at?->toIso8601String(),
                    'started_at' => $room->started_at?->toIso8601String(),
                    'ended_at' => $room->ended_at?->toIso8601String(),
                    'duration_seconds' => $room->duration_seconds,
                    'recording_enabled' => $room->recording_enabled,
                    'recording_consent_given' => $room->recording_consent_given,
                    'is_public' => $room->is_public,
                    'max_participants' => $room->max_participants,
                    'participants_count' => $room->getActiveParticipantsCount(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get recording download URL
     */
    public function downloadRecording(Request $request, string $recordingId): JsonResponse
    {
        try {
            $recording = \App\Models\Video\VideoRecording::findOrFail($recordingId);

            $presignedUrl = $this->recordingService->generatePresignedUrl($recording, 15);

            return response()->json([
                'success' => true,
                'url' => $presignedUrl,
                'expires_in_minutes' => 15,
                'title' => $recording->title,
                'duration_seconds' => $recording->duration_seconds,
                'size_bytes' => $recording->size_bytes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
