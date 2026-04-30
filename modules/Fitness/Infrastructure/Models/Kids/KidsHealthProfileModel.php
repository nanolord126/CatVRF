<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Kids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Kids\Entities\KidsHealthProfile;

final class KidsHealthProfileModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_kids_health_profiles';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'age_group',
        'birth_date',
        'parent_name',
        'parent_phone',
        'parent_email',
        'has_allergies',
        'allergies',
        'has_asthma',
        'has_heart_condition',
        'medications',
        'emergency_contact',
        'emergency_phone',
        'physical_limitations',
    ];

    protected $casts = [
        'physical_limitations' => 'array',
        'birth_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(\Modules\Fitness\Infrastructure\Models\ClientModel::class, 'client_id');
    }

    public static function fromDomain(KidsHealthProfile $profile): self
    {
        return new self([
            'id' => $profile->id > 0 ? $profile->id : null,
            'tenant_id' => $profile->tenantId,
            'client_id' => $profile->clientId,
            'age_group' => $profile->ageGroup,
            'birth_date' => $profile->birthDate?->toDateString(),
            'parent_name' => $profile->parentName,
            'parent_phone' => $profile->parentPhone,
            'parent_email' => $profile->parentEmail,
            'has_allergies' => $profile->hasAllergies,
            'allergies' => $profile->allergies,
            'has_asthma' => $profile->hasAsthma,
            'has_heart_condition' => $profile->hasHeartCondition,
            'medications' => $profile->medications,
            'emergency_contact' => $profile->emergencyContact,
            'emergency_phone' => $profile->emergencyPhone,
            'physical_limitations' => $profile->physicalLimitations,
        ]);
    }

    public function updateFromDomain(KidsHealthProfile $profile): void
    {
        $this->age_group = $profile->ageGroup;
        $this->birth_date = $profile->birthDate?->toDateString();
        $this->parent_name = $profile->parentName;
        $this->parent_phone = $profile->parentPhone;
        $this->parent_email = $profile->parentEmail;
        $this->has_allergies = $profile->hasAllergies;
        $this->allergies = $profile->allergies;
        $this->has_asthma = $profile->hasAsthma;
        $this->has_heart_condition = $profile->hasHeartCondition;
        $this->medications = $profile->medications;
        $this->emergency_contact = $profile->emergencyContact;
        $this->emergency_phone = $profile->emergencyPhone;
        $this->physical_limitations = $profile->physicalLimitations;
    }

    public function toDomain(): KidsHealthProfile
    {
        return new KidsHealthProfile(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            ageGroup: $this->age_group,
            birthDate: $this->birth_date ? \Carbon\CarbonImmutable::parse($this->birth_date) : null,
            parentName: $this->parent_name,
            parentPhone: $this->parent_phone,
            parentEmail: $this->parent_email,
            hasAllergies: $this->has_allergies,
            allergies: $this->allergies,
            hasAsthma: $this->has_asthma,
            hasHeartCondition: $this->has_heart_condition,
            medications: $this->medications,
            emergencyContact: $this->emergency_contact,
            emergencyPhone: $this->emergency_phone,
            physicalLimitations: $this->physical_limitations,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
