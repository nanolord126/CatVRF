<?php

declare(strict_types=1);

namespace Modules\Video\Application\Services;

use Illuminate\Log\LogManager;
use Modules\Video\Domain\DTOs\CreateRoomDTO;
use Modules\Video\Domain\DTOs\JoinRoomDTO;
use Modules\Video\Domain\Entities\VideoParticipant;
use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\Exceptions\VideoRoomException;
use Modules\Video\Domain\Repositories\VideoRoomRepositoryInterface;
use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\RoomStatus;
use Modules\Video\Domain\ValueObjects\RoomType;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;

final readonly class VideoRoomService
{
    use WithAuditLogging;

    public function __construct(
        private VideoRoomRepositoryInterface $roomRepository,
        private LiveKitService $liveKitService,
        private readonly AuditService $auditService,
        private readonly LogManager $logger,
    ) {
    }

    public function createRoom(CreateRoomDTO $dto): VideoRoom
    {
        $room = VideoRoom::create(
            type: $dto->type,
            hostId: $dto->hostId,
            petId: $dto->petId,
            scheduledAt: $dto->scheduledAt,
            metadata: $dto->metadata,
        );

        // Create LiveKit room
        $livekitRoomName = $this->generateLivekitRoomName($room);
        $this->liveKitService->createRoom($livekitRoomName, $dto->type);

        $room = $room->withLivekitCredentials($livekitRoomName, null);
        $this->roomRepository->save($room);

        // Add host as participant
        $hostParticipant = VideoParticipant::create(
            roomId: $room->id,
            userId: $dto->hostId,
            role: ParticipantRole::HOST,
        );
        $this->roomRepository->saveParticipant($hostParticipant);

        // Add additional participants if provided
        if ($dto->participantIds !== null) {
            foreach ($dto->participantIds as $participantId) {
                $participant = VideoParticipant::create(
                    roomId: $room->id,
                    userId: $participantId,
                    role: ParticipantRole::VIEWER,
                );
                $this->roomRepository->saveParticipant($participant);
            }
        }

        $this->logger->info('Video room created', [
            'room_id' => $room->id,
            'type' => $room->type->value,
            'host_id' => $room->hostId,
        ]);

        $this->logCreated('VideoRoom', $room->id, [
            'type' => $room->type->value,
            'host_id' => $room->hostId,
            'pet_id' => $room->petId,
        ], $dto->hostId);

        return $room;
    }

    public function startRoom(string $roomId): VideoRoom
    {
        $room = $this->roomRepository->findById($roomId);

        if ($room === null) {
            throw VideoRoomException::roomNotFound($roomId);
        }

        if ($room->status !== RoomStatus::SCHEDULED) {
            throw VideoRoomException::roomNotActive($roomId);
        }

        $room = $room->start();
        $this->roomRepository->save($room);

        $this->logger->info('Video room started', ['room_id' => $roomId]);

        $this->logAction('video_room_started', 'VideoRoom', $roomId, [], $room->hostId);

        return $room;
    }

    public function endRoom(string $roomId, ?string $recordingUrl = null): VideoRoom
    {
        $room = $this->roomRepository->findById($roomId);

        if ($room === null) {
            throw VideoRoomException::roomNotFound($roomId);
        }

        if ($room->status !== RoomStatus::ACTIVE) {
            throw VideoRoomException::roomNotActive($roomId);
        }

        // Stop LiveKit room
        if ($room->livekitRoomName !== null) {
            $this->liveKitService->deleteRoom($room->livekitRoomName);
        }

        $room = $room->end($recordingUrl);
        $this->roomRepository->save($room);

        // Mark all participants as left
        $participants = $this->roomRepository->findParticipantsByRoom($roomId);
        foreach ($participants as $participant) {
            if ($participant->isInRoom()) {
                $participant = $participant->leave();
                $this->roomRepository->saveParticipant($participant);
            }
        }

        $this->logger->info('Video room ended', [
            'room_id' => $roomId,
            'recording_url' => $recordingUrl,
        ]);

        $this->logAction('video_room_ended', 'VideoRoom', $roomId, [
            'recording_url' => $recordingUrl,
        ], $room->hostId);

        return $room;
    }

    public function joinRoom(JoinRoomDTO $dto): VideoParticipant
    {
        $room = $this->roomRepository->findById($dto->roomId);

        if ($room === null) {
            throw VideoRoomException::roomNotFound($dto->roomId);
        }

        if (!$room->status->canJoin()) {
            throw VideoRoomException::roomNotActive($dto->roomId);
        }

        // Check max participants
        $currentParticipants = count($this->roomRepository->findActiveParticipants($dto->roomId));
        if ($currentParticipants >= $room->type->getMaxParticipants()) {
            throw VideoRoomException::maxParticipantsExceeded(
                $room->type->getMaxParticipants(),
                $currentParticipants
            );
        }

        // Check recording consent for consultations
        if ($room->type->requiresRecordingConsent() && !$room->recordingConsentGiven) {
            throw VideoRoomException::recordingConsentRequired();
        }

        $participant = $this->roomRepository->findParticipant($dto->userId, $dto->roomId);

        if ($participant === null) {
            $participant = VideoParticipant::create(
                roomId: $dto->roomId,
                userId: $dto->userId,
                role: $dto->role,
            );
        }

        // Generate LiveKit access token
        $livekitIdentity = $this->generateLivekitIdentity($dto->userId, $dto->role);
        $accessToken = $this->liveKitService->generateAccessToken(
            $room->livekitRoomName,
            $livekitIdentity,
            $dto->role
        );

        $participant = $participant
            ->withLivekitIdentity($livekitIdentity)
            ->join();

        $this->roomRepository->saveParticipant($participant);

        $this->logger->info('Participant joined room', [
            'room_id' => $dto->roomId,
            'user_id' => $dto->userId,
            'role' => $dto->role->value,
        ]);

        $this->logAction('participant_joined_room', 'VideoParticipant', $participant->id, [
            'room_id' => $dto->roomId,
            'user_id' => $dto->userId,
            'role' => $dto->role->value,
        ], $dto->userId);

        return $participant;
    }

    public function leaveRoom(string $roomId, string $userId): VideoParticipant
    {
        $participant = $this->roomRepository->findParticipant($userId, $roomId);

        if ($participant === null) {
            throw VideoRoomException::participantNotFound($userId);
        }

        $participant = $participant->leave();
        $this->roomRepository->saveParticipant($participant);

        $this->logger->info('Participant left room', [
            'room_id' => $roomId,
            'user_id' => $userId,
        ]);

        $this->logAction('participant_left_room', 'VideoParticipant', $participant->id, [
            'room_id' => $roomId,
            'user_id' => $userId,
        ], $userId);

        return $participant;
    }

    public function giveRecordingConsent(string $roomId): VideoRoom
    {
        $room = $this->roomRepository->findById($roomId);

        if ($room === null) {
            throw VideoRoomException::roomNotFound($roomId);
        }

        $room = $room->giveRecordingConsent();
        $this->roomRepository->save($room);

        $this->logger->info('Recording consent given', ['room_id' => $roomId]);

        $this->logAction('recording_consent_given', 'VideoRoom', $roomId, [], $room->hostId);

        return $room;
    }

    public function getRoom(string $roomId): ?VideoRoom
    {
        return $this->roomRepository->findById($roomId);
    }

    public function getParticipantAccessToken(string $roomId, string $userId): string
    {
        $room = $this->roomRepository->findById($roomId);
        $participant = $this->roomRepository->findParticipant($userId, $roomId);

        if ($room === null || $participant === null) {
            throw VideoRoomException::roomNotFound($roomId);
        }

        return $this->liveKitService->generateAccessToken(
            $room->livekitRoomName,
            $participant->livekitParticipantIdentity ?? $this->generateLivekitIdentity($userId, $participant->role),
            $participant->role
        );
    }

    private function generateLivekitRoomName(VideoRoom $room): string
    {
        $tenantId = tenant('id') ?? 'default';
        return "tenant_{$tenantId}_room_{$room->id}";
    }

    private function generateLivekitIdentity(string $userId, ParticipantRole $role): string
    {
        return "{$role->value}_{$userId}";
    }

    /**
     * @return array<VideoRoom>
     */
    public function getActiveRoomsByHost(string $hostId): array
    {
        return $this->roomRepository->findByHost($hostId, RoomStatus::ACTIVE);
    }

    /**
     * @return array<VideoParticipant>
     */
    public function getRoomParticipants(string $roomId): array
    {
        return $this->roomRepository->findParticipantsByRoom($roomId);
    }
}
