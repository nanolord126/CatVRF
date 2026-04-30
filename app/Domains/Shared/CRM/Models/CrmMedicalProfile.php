<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * CrmMedicalProfile — CRM-профиль клиента вертикали Медицина.
 *
 * Мед.карта, анамнез, аллергии, рецепты, вакцинации, страховка.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmMedicalProfile extends Model
{
    use TenantScoped;

    protected $table = 'crm_medical_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'date_of_birth', 'blood_type',
        'chronic_conditions', 'allergies', 'current_medications',
        'vaccination_history', 'lab_results_history', 'preferred_doctor_id',
        'insurance_provider', 'insurance_policy', 'insurance_expires_at',
        'appointment_history', 'prescription_history', 'has_disability',
        'emergency_contact_name', 'emergency_contact_phone', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'chronic_conditions' => 'json',
        'allergies' => 'json',
        'current_medications' => 'json',
        'vaccination_history' => 'json',
        'lab_results_history' => 'json',
        'appointment_history' => 'json',
        'prescription_history' => 'json',
        'date_of_birth' => 'date',
        'insurance_expires_at' => 'date',
        'has_disability' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmMedicalProfile[id=%d]', $this->id ?? 0);
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model): void {
            if (! $model->uuid && $model->isFillable('uuid')) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
