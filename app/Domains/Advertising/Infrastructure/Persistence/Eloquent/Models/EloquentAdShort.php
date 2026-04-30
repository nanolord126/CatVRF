<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models;

use App\Domains\Advertising\Domain\Entities\AdShort as AdShortEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent AdShort Model
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
class EloquentAdShort extends Model
{
    use SoftDeletes;

    protected $table = 'ad_shorts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'video_url',
        'thumbnail_url',
        'duration_seconds',
        'status',
        'start_at',
        'end_at',
        'budget',
        'spent',
        'pricing_model',
        'targeting_criteria',
        'correlation_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'budget' => 'integer',
        'spent' => 'integer',
        'targeting_criteria' => 'array',
    ];

    public function toDomain(): AdShortEntity
    {
        return new AdShortEntity(
            id: $this->id,
            uuid: $this->uuid,
            tenant_id: $this->tenant_id,
            title: $this->title,
            video_url: $this->video_url,
            thumbnail_url: $this->thumbnail_url,
            duration_seconds: $this->duration_seconds,
            status: $this->status,
            start_at: $this->start_at,
            end_at: $this->end_at,
            budget: $this->budget,
            spent: $this->spent,
            pricing_model: $this->pricing_model,
            targeting_criteria: $this->targeting_criteria ?? [],
            correlation_id: $this->correlation_id,
        );
    }

    public static function fromDomain(AdShortEntity $entity): self
    {
        return new self([
            'uuid' => $entity->uuid,
            'tenant_id' => $entity->tenant_id,
            'title' => $entity->title,
            'video_url' => $entity->video_url,
            'thumbnail_url' => $entity->thumbnail_url,
            'duration_seconds' => $entity->duration_seconds,
            'status' => $entity->status,
            'start_at' => $entity->start_at,
            'end_at' => $entity->end_at,
            'budget' => $entity->budget,
            'spent' => $entity->spent,
            'pricing_model' => $entity->pricing_model,
            'targeting_criteria' => $entity->targeting_criteria,
            'correlation_id' => $entity->correlation_id,
        ]);
    }
}
