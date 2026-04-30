<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\PetVaccination;
use Modules\VetGrooming\Domain\Enums\VaccineType;
use Modules\VetGrooming\Domain\Enums\VaccinationStatus;
use Carbon\CarbonImmutable;

class PetVaccinationModel extends Model
{
    use SoftDeletes;

    protected $table = 'pet_vaccinations';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'pet_id',
        'veterinarian_id',
        'clinic_id',
        'vaccine_type',
        'vaccine_name',
        'manufacturer',
        'batch_number',
        'expiration_date',
        'dose_number',
        'total_doses',
        'planned_date',
        'actual_date',
        'next_due_date',
        'status',
        'notes',
        'risk_factors',
        'reaction_data',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'planned_date' => 'date',
        'actual_date' => 'date',
        'next_due_date' => 'date',
        'risk_factors' => 'array',
        'reaction_data' => 'array',
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

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(\App\Models\VeterinaryClinic::class, 'clinic_id');
    }

    public function toDomain(): PetVaccination
    {
        return new PetVaccination(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            petId: $this->pet_id,
            veterinarianId: $this->veterinarian_id,
            clinicId: $this->clinic_id,
            vaccineType: VaccineType::from($this->vaccine_type),
            vaccineName: $this->vaccine_name,
            manufacturer: $this->manufacturer,
            batchNumber: $this->batch_number,
            expirationDate: $this->expiration_date ? CarbonImmutable::parse($this->expiration_date) : null,
            doseNumber: $this->dose_number,
            totalDoses: $this->total_doses,
            plannedDate: $this->planned_date ? CarbonImmutable::parse($this->planned_date) : null,
            actualDate: $this->actual_date ? CarbonImmutable::parse($this->actual_date) : null,
            nextDueDate: $this->next_due_date ? CarbonImmutable::parse($this->next_due_date) : null,
            status: VaccinationStatus::from($this->status),
            notes: $this->notes,
            riskFactors: $this->risk_factors,
            reactionData: $this->reaction_data,
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(PetVaccination $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->pet_id = $entity->petId;
        $model->veterinarian_id = $entity->veterinarianId;
        $model->clinic_id = $entity->clinicId;
        $model->vaccine_type = $entity->vaccineType->value;
        $model->vaccine_name = $entity->vaccineName;
        $model->manufacturer = $entity->manufacturer;
        $model->batch_number = $entity->batchNumber;
        $model->expiration_date = $entity->expirationDate?->toDateString();
        $model->dose_number = $entity->doseNumber;
        $model->total_doses = $entity->totalDoses;
        $model->planned_date = $entity->plannedDate?->toDateString();
        $model->actual_date = $entity->actualDate?->toDateString();
        $model->next_due_date = $entity->nextDueDate?->toDateString();
        $model->status = $entity->status->value;
        $model->notes = $entity->notes;
        $model->risk_factors = $entity->riskFactors;
        $model->reaction_data = $entity->reactionData;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
