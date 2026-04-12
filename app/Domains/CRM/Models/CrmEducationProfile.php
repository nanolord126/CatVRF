<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmEducationProfile — CRM-профиль клиента вертикали Образование.
 *
 * Курсы, прогресс, сертификаты, расписание, предпочтения обучения.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmEducationProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'crm_education_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'education_level', 'learning_goals',
        'preferred_subjects', 'completed_courses', 'active_enrollments',
        'learning_style', 'preferred_language', 'schedule_preferences',
        'preferred_format', 'avg_study_hours_week', 'certifications',
        'skills_acquired', 'total_spent_on_education', 'courses_completed_count',
        'notes', 'correlation_id',
    ];

    protected $casts = [
        'learning_goals' => 'json',
        'preferred_subjects' => 'json',
        'completed_courses' => 'json',
        'active_enrollments' => 'json',
        'schedule_preferences' => 'json',
        'certifications' => 'json',
        'skills_acquired' => 'json',
        'total_spent_on_education' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmEducationProfile[id=%d]', $this->id ?? 0);
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
