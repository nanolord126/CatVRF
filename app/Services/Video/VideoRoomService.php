<?php

declare(strict_types=1);

namespace App\Services\Video;

use App\Models\Video\VideoRoom;
use App\Models\Video\VideoParticipant;
use App\Models\Video\VideoRecording;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use LiveKit\AccessToken;
use LiveKit\VideoGrants;

final readonly class VideoRoomService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
    ) {
        $this->livekitApiKey = config('services.livekit.api_key');
        $this->livekitApiSecret = config('services.livekit.api_secret');
        $this->livekitUrl = config('services.livekit.url');
    }

    private string $livekitApiKey;
    private string $livekitApiSecret;
    private string $livekitUrl;

    /**
     * Create a new video room
     *
     * @param  array  $data  Room data
     * @return VideoRoom
     */
    public function createRoom(array $data): VideoRoom
    {
        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        return $this->db->transaction(function () use ($data, $tenantId) {
            $room = VideoRoom::create([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'type' => $data['type'] ?? 'consultation',
                'status' => 'scheduled',
                'host_id' => $data['host_id'],
                'host_type' => $data['host_type'],
                'pet_id' => $data['pet_id'] ?? null,
                'pet_type' => $data['pet_type'] ?? null,
                'room_name' => $data['room_name'] ?? null,
                'description' => $data['description'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? now(),
                'recording_enabled' => $data['recording_enabled'] ?? false,
                'is_public' => $data['is_public'] ?? false,
                'max_participants' => $data['max_participants'] ?? 10,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Add host as first participant
            $this->addParticipant($room, [
                'user_id' => $data['host_id'],
                'user_type' => $data['host_type'],
                'role' => 'host',
                'status' => 'invited',
            ]);

            $this->logger->info('Video room created', [
                'room_id' => $room->id,
                'tenant_id' => $tenantId,
                'type' => $room->type,
            ]);

            return $room;
        });
    }

    /**
     * Start a video room
     *
     * @param  VideoRoom  $room
     * @return VideoRoom
     */
    public function startRoom(VideoRoom $room): VideoRoom
    {
        if (!$room->canStart()) {
            throw new \RuntimeException('Room cannot be started');
        }

        $room->start();

        $this->logger->info('Video room started', [
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
        ]);

        return $room;
    }

    /**
     * End a video room
     *
     * @param  VideoRoom  $room
     * @return VideoRoom
     */
    public function endRoom(VideoRoom $room): VideoRoom
    {
        $room->end();

        // Mark all joined participants as left
        $room->participants()
            ->where('status', 'joined')
            ->each(fn ($participant) => $participant->leave());

        $this->logger->info('Video room ended', [
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
            'duration_seconds' => $room->duration_seconds,
        ]);

        return $room;
    }

    /**
     * Add participant to room
     *
     * @param  VideoRoom  $room
     * @param  array  $data
     * @return VideoParticipant
     */
    public function addParticipant(VideoRoom $room, array $data): VideoParticipant
    {
        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        $participant = VideoParticipant::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'video_room_id' => $room->id,
            'user_id' => $data['user_id'],
            'user_type' => $data['user_type'],
            'role' => $data['role'] ?? 'participant',
            'status' => $data['status'] ?? 'invited',
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->logger->info('Participant added to video room', [
            'room_id' => $room->id,
            'participant_id' => $participant->id,
            'role' => $participant->role,
        ]);

        return $participant;
    }

    /**
     * Join participant to room
     *
     * @param  VideoRoom  $room
     * @param  string  $userId
     * @param  string  $userType
     * @return VideoParticipant
     */
    public function joinRoom(VideoRoom $room, string $userId, string $userType): VideoParticipant
    {
        if ($room->isFull()) {
            throw new \RuntimeException('Room is full');
        }

        $participant = $room->participants()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->firstOrFail();

        $participant->join();

        $this->logger->info('Participant joined video room', [
            'room_id' => $room->id,
            'participant_id' => $participant->id,
        ]);

        return $participant;
    }

    /**
     * Leave room
     *
     * @param  VideoParticipant  $participant
     * @return VideoParticipant
     */
    public function leaveRoom(VideoParticipant $participant): VideoParticipant
    {
        $participant->leave();

        $this->logger->info('Participant left video room', [
            'room_id' => $participant->video_room_id,
            'participant_id' => $participant->id,
        ]);

        return $participant;
    }

    /**
     * Generate LiveKit access token for participant
     *
     * @param  VideoRoom  $room
     * @param  VideoParticipant  $participant
     * @param  int  $expiresInMinutes
     * @return string
     */
    public function generateLiveKitToken(
        VideoRoom $room,
        VideoParticipant $participant,
        int $expiresInMinutes = 60
    ): string {
        $token = new AccessToken($this->livekitApiKey, $this->livekitApiSecret);

        $grants = new VideoGrants();
        $grants->setRoomJoin(true);
        $grants->setRoom($room->livekit_room_name);
        $grants->setIdentity($participant->livekit_participant_identity);

        if ($participant->isHost() || $participant->isCoHost()) {
            $grants->setCanPublish(true);
            $grants->setCanSubscribe(true);
            $grants->setCanPublishData(true);
        } else {
            $grants->setCanPublish(false);
            $grants->setCanSubscribe(true);
            $grants->setCanPublishData(false);
        }

        $token->addGrant($grants);
        $token->setIdentity($participant->livekit_participant_identity);
        $token->setValidFor($expiresInMinutes * 60);

        return $token->toJwt();
    }

    /**
     * Generate room access token (for validation)
     *
     * @param  VideoRoom  $room
     * @param  int  $expiresInMinutes
     * @return string
     */
    public function generateRoomAccessToken(VideoRoom $room, int $expiresInMinutes = 60): string
    {
        return $room->generateAccessToken($expiresInMinutes);
    }

    /**
     * Validate room access token
     *
     * @param  VideoRoom  $room
     * @param  string  $token
     * @return bool
     */
    public function validateRoomAccessToken(VideoRoom $room, string $token): bool
    {
        return $room->isAccessTokenValid($token);
    }

    /**
     * Grant recording consent
     *
     * @param  VideoRoom  $room
     * @param  string  $signature
     * @return bool
     */
    public function grantRecordingConsent(VideoRoom $room, string $signature): bool
    {
        if (!$room->recording_enabled) {
            throw new \RuntimeException('Recording is not enabled for this room');
        }

        $room->giveRecordingConsent($signature);

        $this->logger->info('Recording consent granted', [
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
        ]);

        return true;
    }

    /**
     * Enable recording for room
     *
     * @param  VideoRoom  $room
     * @return bool
     */
    public function enableRecording(VideoRoom $room): bool
    {
        if (!$room->hasRecordingConsent()) {
            throw new \RuntimeException('Recording consent must be granted before enabling recording');
        }

        $room->update(['recording_enabled' => true]);

        $this->logger->info('Recording enabled for room', [
            'room_id' => $room->id,
            'tenant_id' => $room->tenant_id,
        ]);

        return true;
    }

    /**
     * Create recording for room
     *
     * @param  VideoRoom  $room
     * @param  array  $data
     * @return VideoRecording
     */
    public function createRecording(VideoRoom $room, array $data = []): VideoRecording
    {
        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        $recording = VideoRecording::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenantId,
            'video_room_id' => $room->id,
            'title' => $data['title'] ?? $room->room_name ?? 'Recording',
            'description' => $data['description'] ?? null,
            'storage_disk' => $data['storage_disk'] ?? 's3',
            'path' => $data['path'] ?? "tenant/{$tenantId}/videos/{$room->id}",
            'filename' => $data['filename'] ?? "recording_{$room->id}.mp4",
            'mime_type' => 'video/mp4',
            'processing_status' => 'pending',
            'consent_given' => $room->hasRecordingConsent(),
            'consent_signature' => $room->recording_consent_signature,
            'consent_given_at' => $room->recording_consent_given_at,
            'is_public' => $data['is_public'] ?? false,
            'available_until' => $data['available_until'] ?? now()->addDays(30),
        ]);

        $this->logger->info('Video recording created', [
            'recording_id' => $recording->id,
            'room_id' => $room->id,
            'tenant_id' => $tenantId,
        ]);

        return $recording;
    }

    /**
     * Get active rooms for tenant
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveRooms()
    {
        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        return VideoRoom::where('tenant_id', $tenantId)
            ->active()
            ->with(['participants'])
            ->get();
    }

    /**
     * Get scheduled rooms for tenant
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getScheduledRooms()
    {
        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        return VideoRoom::where('tenant_id', $tenantId)
            ->scheduled()
            ->where('scheduled_at', '>', now())
            ->with(['participants'])
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Get room by ID with tenant isolation
     *
     * @param  string  $roomId
     * @return VideoRoom
     */
    public function getRoom(string $roomId): VideoRoom
    {
        $tenantId = tenant()?->id ?? throw new \RuntimeException('Tenant not found');

        return VideoRoom::where('tenant_id', $tenantId)
            ->where('id', $roomId)
            ->with(['participants', 'recordings'])
            ->firstOrFail();
    }
}
