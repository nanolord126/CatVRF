<?php

namespace App\Domains\Clinic\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MedicalCard Model - Медицинская карточка пациента
 * 
 * Production ready model для управления медицинскими данными в домене Clinic.
 * Содержит информацию о пациенте, его истории болезней и аллергиях.
 */
class MedicalCard extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'medical_cards';
    protected $guarded = [];

    protected $casts = [
        'allergies' => 'array',
        'medical_history' => 'array',
        'last_check_up' => 'datetime',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'patient_id',
        'blood_type',
        'height',
        'weight',
        'allergies',
        'chronic_diseases',
        'medical_history',
        'last_check_up',
        'status',
        'correlation_id',
        'metadata',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'patient_id');
    }

    // ============================================
    // BUSINESS LOGIC
    // ============================================

    public function hasAllergies(): bool
    {
        return !empty($this->allergies) && count($this->allergies) > 0;
    }

    public function getAllergyWarnings(): array
    {
        return $this->allergies ?? [];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function needsCheckup(): bool
    {
        return $this->last_check_up === null || $this->last_check_up->diffInMonths(now()) >= 12;
    }

    public function recordCheckup(): bool
    {
        return $this->update(['last_check_up' => now()]);
    }
}
