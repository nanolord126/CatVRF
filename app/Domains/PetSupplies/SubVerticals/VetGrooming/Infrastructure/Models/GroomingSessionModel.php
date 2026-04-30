<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Infrastructure\Models;

use App\Traits\HasOptimizedMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\VetGrooming\Domain\Entities\GroomingSession;
use Modules\VetGrooming\Domain\Enums\GroomingServiceType;
use Modules\VetGrooming\Domain\Enums\GroomingStatus;
use Modules\VetGrooming\Domain\Enums\BehaviorRating;
use Modules\Video\Domain\Traits\HasVideoTrait;
use Carbon\CarbonImmutable;

class GroomingSessionModel extends Model
{
    use SoftDeletes;
    use HasOptimizedMedia;
    use HasVideoTrait;

    protected $table = 'grooming_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'pet_id',
        'groomer_id',
        'appointment_id',
        'service_id',
        'started_at',
        'completed_at',
        'duration_minutes',
        'service_type',
        'behavior_rating',
        'behavior_notes',
        'products_used',
        'before_photos',
        'after_photos',
        'allergy_alerts',
        'medical_notes',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'behavior_rating' => BehaviorRating::class,
        'products_used' => 'array',
        'before_photos' => 'array',
        'after_photos' => 'array',
        'tags' => 'array',
    ];

    public function pet(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Pet::class, 'pet_id');
    }

    public function groomer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Veterinarian::class, 'groomer_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(\App\Models\VeterinaryClinic::class, 'clinic_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(\App\Models\VeterinaryAppointment::class, 'appointment_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(\App\Models\VeterinaryService::class, 'service_id');
    }

    public function toDomain(): GroomingSession
    {
        return new GroomingSession(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            clinicId: $this->clinic_id,
            petId: $this->pet_id,
            groomerId: $this->groomer_id,
            appointmentId: $this->appointment_id,
            serviceId: $this->service_id,
            startedAt: $this->started_at ? CarbonImmutable::parse($this->started_at) : null,
            completedAt: $this->completed_at ? CarbonImmutable::parse($this->completed_at) : null,
            durationMinutes: $this->duration_minutes,
            serviceType: GroomingServiceType::from($this->service_type),
            behaviorRating: $this->behavior_rating,
            behaviorNotes: $this->behavior_notes,
            productsUsed: $this->products_used,
            beforePhotos: $this->before_photos,
            afterPhotos: $this->after_photos,
            allergyAlerts: $this->allergy_alerts,
            medicalNotes: $this->medical_notes,
            status: GroomingStatus::from($this->status),
            correlationId: $this->correlation_id,
            tags: $this->tags,
            createdAt: CarbonImmutable::parse($this->created_at),
            updatedAt: CarbonImmutable::parse($this->updated_at),
            deletedAt: $this->deleted_at ? CarbonImmutable::parse($this->deleted_at) : null,
        );
    }

    public static function fromDomain(GroomingSession $entity): self
    {
        $model = new self();
        $model->id = $entity->id;
        $model->uuid = $entity->uuid;
        $model->tenant_id = $entity->tenantId;
        $model->clinic_id = $entity->clinicId;
        $model->pet_id = $entity->petId;
        $model->groomer_id = $entity->groomerId;
        $model->appointment_id = $entity->appointmentId;
        $model->service_id = $entity->serviceId;
        $model->started_at = $entity->startedAt?->toDateTimeString();
        $model->completed_at = $entity->completedAt?->toDateTimeString();
        $model->duration_minutes = $entity->durationMinutes;
        $model->service_type = $entity->serviceType->value;
        $model->behavior_rating = $entity->behaviorRating?->value;
        $model->behavior_notes = $entity->behaviorNotes;
        $model->products_used = $entity->productsUsed;
        $model->before_photos = $entity->beforePhotos;
        $model->after_photos = $entity->afterPhotos;
        $model->allergy_alerts = $entity->allergyAlerts;
        $model->medical_notes = $entity->medicalNotes;
        $model->status = $entity->status->value;
        $model->correlation_id = $entity->correlationId;
        $model->tags = $entity->tags;

        return $model;
    }
}
