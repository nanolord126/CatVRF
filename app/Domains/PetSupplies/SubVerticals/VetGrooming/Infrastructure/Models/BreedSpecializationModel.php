<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\BreedSpecialization;
use Carbon\CarbonImmutable;

class BreedSpecializationModel extends Model
{
    use SoftDeletes;

    protected $table = 'breed_specializations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'master_id',
        'breed_group',
        'proficiency_level',
        'total_groomings_completed',
        'certifications_count',
        'average_rating',
        'repeat_clients',
        'is_active',
        'notes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'total_groomings_completed' => 'integer',
        'certifications_count' => 'integer',
        'average_rating' => 'decimal:2',
        'repeat_clients' => 'integer',
        'is_active' => 'boolean',
        'tags' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Veterinarian::class, 'master_id');
    }

    public function toDomain(): BreedSpecialization
    {
        return new BreedSpecialization(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            masterId: $this->master_id,
            breedGroup: $this->breed_group,
            proficiencyLevel: $this->proficiency_level,
            totalGroomingsCompleted: $this->total_groomings_completed,
            certificationsCount: $this->certifications_count,
            averageRating: $this->average_rating !== null ? (float) $this->average_rating : null,
            repeatClients: $this->repeat_clients,
            isActive: $this->is_active,
            notes: $this->notes,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(BreedSpecialization $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->master_id = $entity->masterId;
        $model->breed_group = $entity->breedGroup;
        $model->proficiency_level = $entity->proficiencyLevel;
        $model->total_groomings_completed = $entity->totalGroomingsCompleted;
        $model->certifications_count = $entity->certificationsCount;
        $model->average_rating = $entity->averageRating;
        $model->repeat_clients = $entity->repeatClients;
        $model->is_active = $entity->isActive;
        $model->notes = $entity->notes;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
