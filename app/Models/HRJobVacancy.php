<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
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
     * AI Integration for Dynamic Salary Adjustments
     * Рассчитывает скорректированную зарплату на основе рынка и компетенций
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        // Получить среднюю зарплату для этой должности за последние 90 дней
        $marketAverage = \DB::table('hr_salary_history')
            ->where('job_title', $this->title)
            ->where('created_at', '>=', now()->subDays(90))
            ->avg('salary');
        
        if (!$marketAverage) {
            $marketAverage = $basePrice; // Fallback to base if no history
        }
        
        // Скорректировать по опыту требуемому
        $experienceMultiplier = match (true) {
            $this->experience_required >= 5 => 1.25,
            $this->experience_required >= 3 => 1.15,
            $this->experience_required >= 1 => 1.05,
            default => 1.0,
        };
        
        // Скорректировать по количеству открытых вакансий (спрос/предложение)
        $openVacanciesCount = self::where('status', 'open')
            ->where('job_title', $this->title)
            ->where('company_id', $this->company_id)
            ->count();
        
        $competitionMultiplier = match (true) {
            $openVacanciesCount >= 5 => 1.1,  // Высокий спрос - повысить зарплату
            $openVacanciesCount >= 2 => 1.05,
            default => 0.95,
        };
        
        $adjustedPrice = $marketAverage * $experienceMultiplier * $competitionMultiplier;
        
        // Убедиться, что не ниже базовой ставки компании
        return max($adjustedPrice, $basePrice * 0.9);
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










