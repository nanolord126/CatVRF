<?php

declare(strict_types=1);

namespace Modules\Fitness\Infrastructure\Models\Senior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Fitness\Domain\Senior\Entities\SeniorHealthProfile;

final class SeniorHealthProfileModel extends Model
{
    use SoftDeletes;

    protected $table = 'fitness_senior_health_profiles';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'has_heart_condition',
        'has_diabetes',
        'has_joint_problems',
        'has_mobility_limitations',
        'has_balance_issues',
        'medications',
        'allergies',
        'emergency_contact',
        'emergency_phone',
        'fitness_level',
        'physical_limitations',
    ];

    protected $casts = [
        'physical_limitations' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(\Modules\Fitness\Infrastructure\Models\ClientModel::class, 'client_id');
    }

    public static function fromDomain(SeniorHealthProfile $profile): self
    {
        return new self([
            'id' => $profile->id > 0 ? $profile->id : null,
            'tenant_id' => $profile->tenantId,
            'client_id' => $profile->clientId,
            'has_heart_condition' => $profile->hasHeartCondition,
            'has_diabetes' => $profile->hasDiabetes,
            'has_joint_problems' => $profile->hasJointProblems,
            'has_mobility_limitations' => $profile->hasMobilityLimitations,
            'has_balance_issues' => $profile->hasBalanceIssues,
            'medications' => $profile->medications,
            'allergies' => $profile->allergies,
            'emergency_contact' => $profile->emergencyContact,
            'emergency_phone' => $profile->emergencyPhone,
            'fitness_level' => $profile->fitnessLevel,
            'physical_limitations' => $profile->physicalLimitations,
        ]);
    }

    public function updateFromDomain(SeniorHealthProfile $profile): void
    {
        $this->has_heart_condition = $profile->hasHeartCondition;
        $this->has_diabetes = $profile->hasDiabetes;
        $this->has_joint_problems = $profile->hasJointProblems;
        $this->has_mobility_limitations = $profile->hasMobilityLimitations;
        $this->has_balance_issues = $profile->hasBalanceIssues;
        $this->medications = $profile->medications;
        $this->allergies = $profile->allergies;
        $this->emergency_contact = $profile->emergencyContact;
        $this->emergency_phone = $profile->emergencyPhone;
        $this->fitness_level = $profile->fitnessLevel;
        $this->physical_limitations = $profile->physicalLimitations;
    }

    public function toDomain(): SeniorHealthProfile
    {
        return new SeniorHealthProfile(
            id: $this->id,
            tenantId: $this->tenant_id,
            clientId: $this->client_id,
            hasHeartCondition: $this->has_heart_condition,
            hasDiabetes: $this->has_diabetes,
            hasJointProblems: $this->has_joint_problems,
            hasMobilityLimitations: $this->has_mobility_limitations,
            hasBalanceIssues: $this->has_balance_issues,
            medications: $this->medications,
            allergies: $this->allergies,
            emergencyContact: $this->emergency_contact,
            emergencyPhone: $this->emergency_phone,
            fitnessLevel: $this->fitness_level,
            physicalLimitations: $this->physical_limitations,
            createdAt: \Carbon\CarbonImmutable::parse($this->created_at),
            updatedAt: \Carbon\CarbonImmutable::parse($this->updated_at),
        );
    }
}
