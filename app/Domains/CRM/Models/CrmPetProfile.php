<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmPetProfile — CRM-профиль клиента вертикали Питомцы.
 *
 * Питомцы, вакцинации, корм, ветеринарная карта, груминг.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmPetProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'crm_pet_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'pets', 'vaccination_schedule',
        'medical_conditions', 'dietary_needs', 'preferred_brands',
        'grooming_schedule', 'preferred_vet_id', 'vet_visit_history',
        'needs_pet_sitting', 'needs_dog_walking', 'insurance_info',
        'monthly_pet_budget', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'pets' => 'json',
        'vaccination_schedule' => 'json',
        'medical_conditions' => 'json',
        'dietary_needs' => 'json',
        'preferred_brands' => 'json',
        'grooming_schedule' => 'json',
        'vet_visit_history' => 'json',
        'insurance_info' => 'json',
        'needs_pet_sitting' => 'boolean',
        'needs_dog_walking' => 'boolean',
        'monthly_pet_budget' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmPetProfile[id=%d]', $this->id ?? 0);
    }

    /**
     * Boot методы модели — global scopes и auto-UUID.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model): void {
            if (!$model->uuid && $model->isFillable('uuid')) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Scope: только активные записи.
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }
}
