<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\PetChronicCondition;
use Modules\VetGrooming\Domain\Enums\ConditionType;
use Carbon\CarbonImmutable;

class PetChronicConditionModel extends Model
{
    use SoftDeletes;

    protected $table = 'pet_chronic_conditions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pet_id',
        'veterinarian_id',
        'condition_type',
        'condition_name',
        'icd_code',
        'diagnosed_date',
        'description',
        'severity',
        'is_active',
        'resolved_date',
        'symptoms',
        'triggers',
        'management_notes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'diagnosed_date' => 'date',
        'resolved_date' => 'date',
        'is_active' => 'boolean',
        'symptoms' => 'array',
        'triggers' => 'array',
        'management_notes' => 'array',
        'tags' => 'array',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pet::class, 'pet_id');
    }

    public function veterinarian(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Veterinarian::class, 'veterinarian_id');
    }

    public function toDomain(): PetChronicCondition
    {
        return new PetChronicCondition(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            petId: $this->pet_id,
            veterinarianId: $this->veterinarian_id,
            conditionType: ConditionType::from($this->condition_type),
            conditionName: $this->condition_name,
            icdCode: $this->icd_code,
            diagnosedDate: $this->diagnosed_date ? CarbonImmutable::parse($this->diagnosed_date) : null,
            description: $this->description,
            severity: $this->severity,
            isActive: $this->is_active,
            resolvedDate: $this->resolved_date ? CarbonImmutable::parse($this->resolved_date) : null,
            symptoms: $this->symptoms,
            triggers: $this->triggers,
            managementNotes: $this->management_notes,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(PetChronicCondition $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->pet_id = $entity->petId;
        $model->veterinarian_id = $entity->veterinarianId;
        $model->condition_type = $entity->conditionType->value;
        $model->condition_name = $entity->conditionName;
        $model->icd_code = $entity->icdCode;
        $model->diagnosed_date = $entity->diagnosedDate?->toDateString();
        $model->description = $entity->description;
        $model->severity = $entity->severity;
        $model->is_active = $entity->isActive;
        $model->resolved_date = $entity->resolvedDate?->toDateString();
        $model->symptoms = $entity->symptoms;
        $model->triggers = $entity->triggers;
        $model->management_notes = $entity->managementNotes;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
