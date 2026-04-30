<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Prenatal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Prenatal\Entities\PrenatalHealthProfile;

final class PrenatalHealthProfileModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_prenatal_health_profiles';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'due_date',
        'trimester',
        'has_high_risk_pregnancy',
        'has_preeclampsia_risk',
        'has_gestational_diabetes',
        'obstetrician_notes',
        'medications',
        'allergies',
        'emergency_contact',
        'emergency_phone',
        'physical_limitations',
    ];

    protected $casts = [
        'physical_limitations' => 'array',
        'due_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(\Modules\Fitness\Infrastructure\Models\ClientModel::class, 'client_id');
    }

    public static function fromDomain(PrenatalHealthProfile $profile): self
    {
        return new self([
            'id' => $profile->id > 0 ? $profile->id : null,
            'tenant_id' => $profile->tenantId,
            'client_id' => $profile->clientId,
            'due_date' => $profile->dueDate->toDateString(),
            'trimester' => $profile->trimester,
            'has_high_risk_pregnancy' => $profile->hasHighRiskPregnancy,
            'has_preeclampsia_risk' => $profile->hasPreeclampsiaRisk,
            'has_gestational_diabetes' => $profile->hasGestationalDiabetes,
            'obstetrician_notes' => $profile->obstetricianNotes,
            'medications' => $profile->medications,
            'allergies' => $profile->allergies,
            'emergency_contact' => $profile->emergencyContact,
            'emergency_phone' => $profile->emergencyPhone,
            'physical_limitations' => $profile->physicalLimitations,
        ]);
    }

    public function updateFromDomain(PrenatalHealthProfile $profile): void
    {
        $this->due_date = $profile->dueDate->toDateString();
        $this->trimester = $profile->trimester;
        $this->has_high_risk_pregnancy = $profile->hasHighRiskPregnancy;
        $this->has_preeclampsia_risk = $profile->hasPreeclampsiaRisk;
        $this->has_gestational_diabetes = $profile->hasGestationalDiabetes;
        $this->obstetrician_notes = $profile->obstetricianNotes;
        $this->medications = $profile->medications;
        $this->allergies = $profile->allergies;
        $this->emergency_contact = $profile->emergencyContact;
        $this->emergency_phone = $profile->emergencyPhone;
        $this->physical_limitations = $profile->physicalLimitations;
    }

    public function toDomain(): PrenatalHealthProfile
    {
        return new PrenatalHealthProfile(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            dueDate: \Carbon\CarbonImmutable::parse($this->due_date),
            trimester: $this->trimester,
            hasHighRiskPregnancy: $this->has_high_risk_pregnancy,
            hasPreeclampsiaRisk: $this->has_preeclampsia_risk,
            hasGestationalDiabetes: $this->has_gestational_diabetes,
            obstetricianNotes: $this->obstetrician_notes,
            medications: $this->medications,
            allergies: $this->allergies,
            emergencyContact: $this->emergency_contact,
            emergencyPhone: $this->emergency_phone,
            physicalLimitations: $this->physical_limitations,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
