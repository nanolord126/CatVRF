<?php

declare(strict_types=1);

namespace Modules\Video\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Video\Domain\Entities\VideoParticipant;
use Modules\Video\Domain\ValueObjects\ParticipantRole;
use Modules\Video\Domain\ValueObjects\ParticipantStatus;

final class VideoParticipantModel extends Model
{
    use HasFactory;

    protected $table = 'video_participants';

    protected $fillable = [
        'id',
        'room_id',
        'user_id',
        'role',
        'status',
        'livekit_participant_identity',
        'joined_at',
        'left_at',
        'metadata',
    ];

    protected $casts = [
        'role' => ParticipantRole::class,
        'status' => ParticipantStatus::class,
        'joined_at' => 'immutable_datetime',
        'left_at' => 'immutable_datetime',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function room(): BelongsTo
    {
        return $this->belongsTo(VideoRoomModel::class, 'room_id');
    }

    public function toDomain(): VideoParticipant
    {
        return new VideoParticipant(
            id: $this->id,
            roomId: $this->room_id,
            userId: $this->user_id,
            role: $this->role,
            status: $this->status,
            livekitParticipantIdentity: $this->livekit_participant_identity,
            joinedAt: $this->joined_at?->toDateTimeImmutable(),
            leftAt: $this->left_at?->toDateTimeImmutable(),
            metadata: $this->metadata,
            createdAt: $this->created_at?->toDateTimeImmutable(),
        );
    }

    public static function fromDomain(VideoParticipant $participant): self
    {
        return new self([
            'id' => $participant->id,
            'room_id' => $participant->roomId,
            'user_id' => $participant->userId,
            'role' => $participant->role,
            'status' => $participant->status,
            'livekit_participant_identity' => $participant->livekitParticipantIdentity,
            'joined_at' => $participant->joinedAt,
            'left_at' => $participant->leftAt,
            'metadata' => $participant->metadata,
        ]);
    }
}
