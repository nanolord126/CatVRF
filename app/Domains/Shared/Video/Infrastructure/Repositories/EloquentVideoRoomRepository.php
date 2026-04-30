<?php

declare(strict_types=1);

namespace Modules\Video\Infrastructure\Repositories;

use Modules\Video\Domain\Entities\VideoParticipant;
use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\Repositories\VideoRoomRepositoryInterface;
use Modules\Video\Domain\ValueObjects\RoomStatus;
use Modules\Video\Domain\ValueObjects\RoomType;
use Modules\Video\Infrastructure\Models\VideoParticipantModel;
use Modules\Video\Infrastructure\Models\VideoRoomModel;

final readonly class EloquentVideoRoomRepository implements VideoRoomRepositoryInterface
{
    public function findById(string $id): ?VideoRoom
    {
        $model = VideoRoomModel::find($id);

        if ($model === null) {
            return null;
        }

        return $model->toDomain();
    }

    public function save(VideoRoom $room): void
    {
        $model = VideoRoomModel::fromDomain($room);
        $model->save();
    }

    public function delete(string $id): void
    {
        VideoRoomModel::destroy($id);
    }

    /**
     * @return array<VideoRoom>
     */
    public function findByHost(string $hostId, ?RoomStatus $status = null): array
    {
        $query = VideoRoomModel::query()->where('host_id', $hostId);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $models = $query->orderBy('created_at', 'desc')->get();

        return $models->map(fn (VideoRoomModel $model) => $model->toDomain())->toArray();
    }

    /**
     * @return array<VideoRoom>
     */
    public function findByPet(string $petId): array
    {
        $models = VideoRoomModel::query()
            ->where('pet_id', $petId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn (VideoRoomModel $model) => $model->toDomain())->toArray();
    }

    /**
     * @return array<VideoRoom>
     */
    public function findActiveRooms(): array
    {
        $models = VideoRoomModel::query()
            ->where('status', RoomStatus::ACTIVE)
            ->get();

        return $models->map(fn (VideoRoomModel $model) => $model->toDomain())->toArray();
    }

    /**
     * @return array<VideoRoom>
     */
    public function findByType(RoomType $type, ?RoomStatus $status = null): array
    {
        $query = VideoRoomModel::query()->where('type', $type);

        if ($status !== null) {
            $query->where('status', $status);
        }

        $models = $query->orderBy('created_at', 'desc')->get();

        return $models->map(fn (VideoRoomModel $model) => $model->toDomain())->toArray();
    }

    public function findParticipant(string $userId, string $roomId): ?VideoParticipant
    {
        $model = VideoParticipantModel::query()
            ->where('user_id', $userId)
            ->where('room_id', $roomId)
            ->first();

        if ($model === null) {
            return null;
        }

        return $model->toDomain();
    }

    public function saveParticipant(VideoParticipant $participant): void
    {
        $model = VideoParticipantModel::fromDomain($participant);
        $model->save();
    }

    /**
     * @return array<VideoParticipant>
     */
    public function findParticipantsByRoom(string $roomId): array
    {
        $models = VideoParticipantModel::query()
            ->where('room_id', $roomId)
            ->orderBy('created_at', 'asc')
            ->get();

        return $models->map(fn (VideoParticipantModel $model) => $model->toDomain())->toArray();
    }

    /**
     * @return array<VideoParticipant>
     */
    public function findActiveParticipants(string $roomId): array
    {
        $models = VideoParticipantModel::query()
            ->where('room_id', $roomId)
            ->where('status', \Modules\Video\Domain\ValueObjects\ParticipantStatus::JOINED)
            ->orderBy('created_at', 'asc')
            ->get();

        return $models->map(fn (VideoParticipantModel $model) => $model->toDomain())->toArray();
    }
}
