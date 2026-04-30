<?php

declare(strict_types=1);

namespace Modules\Video\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Video\Domain\Entities\VideoRoom;
use Modules\Video\Domain\ValueObjects\RoomStatus;
use Modules\Video\Domain\ValueObjects\RoomType;

final class VideoRoomModel extends Model
{
    use HasFactory;

    protected $table = 'video_rooms';

    protected $fillable = [
        'id',
        'tenant_id',
        'type',
        'host_id',
        'pet_id',
        'status',
        'recording_url',
        'recording_consent_given',
        'livekit_room_name',
        'livekit_access_token',
        'scheduled_at',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'type' => RoomType::class,
        'status' => RoomStatus::class,
        'recording_consent_given' => 'boolean',
        'scheduled_at' => 'immutable_datetime',
        'started_at' => 'immutable_datetime',
        'ended_at' => 'immutable_datetime',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function participants(): HasMany
    {
        return $this->hasMany(VideoParticipantModel::class, 'room_id');
    }

    public function toDomain(): VideoRoom
    {
        return new VideoRoom(
            id: $this->id,
            tenantId: $this->tenant_id,
            type: $this->type,
            hostId: $this->host_id,
            petId: $this->pet_id,
            status: $this->status,
            recordingUrl: $this->recording_url,
            recordingConsentGiven: $this->recording_consent_given,
            livekitRoomName: $this->livekit_room_name,
            livekitAccessToken: $this->livekit_access_token,
            scheduledAt: $this->scheduled_at?->toDateTimeImmutable(),
            startedAt: $this->started_at?->toDateTimeImmutable(),
            endedAt: $this->ended_at?->toDateTimeImmutable(),
            metadata: $this->metadata,
            createdAt: $this->created_at?->toDateTimeImmutable(),
            updatedAt: $this->updated_at?->toDateTimeImmutable(),
        );
    }

    public static function fromDomain(VideoRoom $room): self
    {
        return new self([
            'id' => $room->id,
            'tenant_id' => $room->tenantId,
            'type' => $room->type,
            'host_id' => $room->hostId,
            'pet_id' => $room->petId,
            'status' => $room->status,
            'recording_url' => $room->recordingUrl,
            'recording_consent_given' => $room->recordingConsentGiven,
            'livekit_room_name' => $room->livekitRoomName,
            'livekit_access_token' => $room->livekitAccessToken,
            'scheduled_at' => $room->scheduledAt,
            'started_at' => $room->startedAt,
            'ended_at' => $room->endedAt,
            'metadata' => $room->metadata,
        ]);
    }
}
