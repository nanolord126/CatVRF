<?php

declare(strict_types=1);

namespace Modules\Video\Domain\Repositories;

use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\Entities\VideoParticipant;
use Modules\Video\Domain\ValueObjects\RoomStatus;
use Modules\Video\Domain\ValueObjects\RoomType;

interface VideoRoomRepositoryInterface
{
    public function findById(string $id): ?VideoRoom;

    public function save(VideoRoom $room): void;

    public function delete(string $id): void;

    /**
     * @return array<VideoRoom>
     */
    public function findByHost(string $hostId, ?RoomStatus $status = null): array;

    /**
     * @return array<VideoRoom>
     */
    public function findByPet(string $petId): array;

    /**
     * @return array<VideoRoom>
     */
    public function findActiveRooms(): array;

    /**
     * @return array<VideoRoom>
     */
    public function findByType(RoomType $type, ?RoomStatus $status = null): array;

    public function findParticipant(string $userId, string $roomId): ?VideoParticipant;

    public function saveParticipant(VideoParticipant $participant): void;

    /**
     * @return array<VideoParticipant>
     */
    public function findParticipantsByRoom(string $roomId): array;

    /**
     * @return array<VideoParticipant>
     */
    public function findActiveParticipants(string $roomId): array;
}
