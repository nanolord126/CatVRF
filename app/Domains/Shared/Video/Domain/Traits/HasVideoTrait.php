<?php

declare(strict_types=1);

namespace Modules\Video\Domain\Traits;

use Modules\Video\Application\Services\VideoRoomService;
use Modules\Video\Domain\DTOs\CreateRoomDTO;
use Modules\Video\Domain\DTOs\JoinRoomDTO;
use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\RoomType;

trait HasVideoTrait
{
    /**
     * Create a video consultation room
     */
    public function createConsultationRoom(?string $petId = null, ?\DateTimeImmutable $scheduledAt = null): VideoRoom
    {
        $dto = new CreateRoomDTO(
            type: RoomType::CONSULTATION,
            hostId: (string) $this->getKey(),
            petId: $petId,
            scheduledAt: $scheduledAt,
            metadata: null,
            participantIds: null,
        );

        $service = app(VideoRoomService::class);
        return $service->createRoom($dto);
    }

    /**
     * Create a grooming demo room
     */
    public function createGroomingDemoRoom(?string $petId = null): VideoRoom
    {
        $dto = new CreateRoomDTO(
            type: RoomType::GROOMING_DEMO,
            hostId: (string) $this->getKey(),
            petId: $petId,
            scheduledAt: null,
            metadata: null,
            participantIds: null,
        );

        $service = app(VideoRoomService::class);
        return $service->createRoom($dto);
    }

    /**
     * Create a masterclass room
     */
    public function createMasterclassRoom(array $participantIds = null): VideoRoom
    {
        $dto = new CreateRoomDTO(
            type: RoomType::MASTERCLASS,
            hostId: (string) $this->getKey(),
            petId: null,
            scheduledAt: null,
            metadata: null,
            participantIds: $participantIds,
        );

        $service = app(VideoRoomService::class);
        return $service->createRoom($dto);
    }

    /**
     * Create a surgery broadcast room
     */
    public function createSurgeryBroadcastRoom(array $participantIds = null): VideoRoom
    {
        $dto = new CreateRoomDTO(
            type: RoomType::SURGERY,
            hostId: (string) $this->getKey(),
            petId: null,
            scheduledAt: null,
            metadata: null,
            participantIds: $participantIds,
        );

        $service = app(VideoRoomService::class);
        return $service->createRoom($dto);
    }

    /**
     * Join a video room
     */
    public function joinVideoRoom(string $roomId, ParticipantRole $role = ParticipantRole::VIEWER)
    {
        $dto = new JoinRoomDTO(
            roomId: $roomId,
            userId: (string) $this->getKey(),
            role: $role,
        );

        $service = app(VideoRoomService::class);
        return $service->joinRoom($dto);
    }

    /**
     * Leave a video room
     */
    public function leaveVideoRoom(string $roomId)
    {
        $service = app(VideoRoomService::class);
        return $service->leaveRoom($roomId, (string) $this->getKey());
    }

    /**
     * Get access token for a video room
     */
    public function getVideoRoomAccessToken(string $roomId): string
    {
        $service = app(VideoRoomService::class);
        return $service->getParticipantAccessToken($roomId, (string) $this->getKey());
    }

    /**
     * Get active video rooms for this user
     *
     * @return array<VideoRoom>
     */
    public function getActiveVideoRooms(): array
    {
        $service = app(VideoRoomService::class);
        return $service->getActiveRoomsByHost((string) $this->getKey());
    }
}
