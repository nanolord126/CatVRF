<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Entities\TrainerReview as TrainerReviewEntity;

final class TrainerReviewModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_trainer_reviews';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'trainer_id',
        'client_id',
        'session_id',
        'uuid',
        'correlation_id',
        'rating',
        'comment',
        'would_recommend',
        'sentiment',
        'is_verified',
        'is_visible',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'rating' => 'integer',
        'would_recommend' => 'boolean',
        'is_verified' => 'boolean',
        'is_visible' => 'boolean',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerModel::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ClientModel::class, 'client_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkoutSessionModel::class, 'session_id');
    }

    public function toDomain(): TrainerReviewEntity
    {
        return new TrainerReviewEntity(
            id: $this->id,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            trainerId: $this->trainer_id,
            clientId: $this->client_id,
            sessionId: $this->session_id,
            uuid: $this->uuid,
            correlationId: $this->correlation_id,
            rating: $this->rating,
            comment: $this->comment,
            wouldRecommend: $this->would_recommend,
            sentiment: $this->sentiment,
            isVerified: $this->is_verified,
            isVisible: $this->is_visible,
            tags: $this->tags,
            metadata: $this->metadata,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }

    public static function fromDomain(TrainerReviewEntity $entity): self
    {
        return new self([
            'id' => $entity->id > 0 ? $entity->id : null,
            'tenant_id' => $entity->tenantId,
            'business_group_id' => $entity->businessGroupId,
            'trainer_id' => $entity->trainerId,
            'client_id' => $entity->clientId,
            'session_id' => $entity->sessionId,
            'uuid' => $entity->uuid ?: \Illuminate\Support\Str::uuid(),
            'correlation_id' => $entity->correlationId,
            'rating' => $entity->rating,
            'comment' => $entity->comment,
            'would_recommend' => $entity->wouldRecommend,
            'sentiment' => $entity->sentiment,
            'is_verified' => $entity->isVerified,
            'is_visible' => $entity->isVisible,
            'tags' => $entity->tags,
            'metadata' => $entity->metadata,
        ]);
    }
}
