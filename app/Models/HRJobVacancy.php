<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Traits\HasEcosystemTracing;
use App\Contracts\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * HR Job Vacancy Model - 2026 Canon.
 * Managed within businesses (Tenants) to recruit talent from the ecosystem.
 */
class HRJobVacancy extends Model implements AIEnableEcosystemEntity
{
    use HasEcosystemTracing, SoftDeletes;

    protected $table = 'hr_job_vacancies';

    protected $fillable = [
        'title',
        'description',
        'skills',
        'salary_min',
        'salary_max',
        'currency',
        'vertical',
        'location_name',
        'latitude',
        'longitude',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'skills' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * AI Integration for Dynamic Pricing (Salary Adjustments)
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        // Placeholder for real AI logic - in 2026 we would call AI service
        return $basePrice; 
    }

    public function getTrustScore(): float
    {
        return 0.95; // Entities have baseline trust in the ecosystem
    }

    public function generateAiChecklist(): array
    {
        return [
            'verify_certifications' => true,
            'background_check_required' => $this->salary_min > 5000,
            'vibe_check_ai_interview' => true,
        ];
    }

    /**
     * Relations
     */
    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hr_vacancy_matches', 'vacancy_id', 'user_id')
            ->withPivot('match_score', 'match_reasons')
            ->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(HRVacancyMatch::class, 'vacancy_id');
    }
}










