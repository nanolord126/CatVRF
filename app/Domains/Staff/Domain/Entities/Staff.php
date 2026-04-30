<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Guard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * Staff — сотрудник в Tenant Panel.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Включает: персональные данные, контакты, фото, роли,
 * статистика эффективности, KPI, оценки, архивация.
 */
final class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';

    protected $fillable = [
        // Tenant scoping
        'tenant_id',
        
        // Персональные данные
        'uuid',
        'first_name',
        'last_name',
        'middle_name',
        
        // Фото
        'photo_url',
        'photo_path',
        
        // Контакты
        'email',
        'phone',
        'telegram',
        'whatsapp',
        
        // Доступ и авторизация
        'user_id',
        'role',
        'permissions',
        
        // Должность и отдел
        'position',
        'department',
        'manager_id',
        
        // Данные о найме
        'hired_at',
        'probation_end_at',
        'employment_type',
        'schedule',
        
        // Зарплата
        'salary',
        'salary_type',
        'salary_details',
        
        // Статус
        'status',
        'archived_at',
        'archive_reason',
        
        // Статистика эффективности (KPI)
        'total_orders_processed',
        'total_revenue_generated',
        'average_order_value',
        'total_customers_served',
        'customer_satisfaction_score',
        'positive_reviews',
        'negative_reviews',
        'complaints',
        'compliments',
        
        // Эффективность работы
        'tasks_completed',
        'tasks_overdue',
        'task_completion_rate',
        'attendance_days',
        'absence_days',
        'attendance_rate',
        
        // Продажи и конверсии
        'sales_count',
        'conversion_rate',
        'upsell_rate',
        'cross_sell_rate',
        
        // Качество работы
        'quality_score',
        'speed_score',
        'accuracy_score',
        
        // Дополнительные метрики
        'referrals_hired',
        'training_completed',
        'overtime_hours',
        'shifts_worked',
        
        // Оценка товаров/услуг
        'average_product_rating',
        'products_rated',
        
        // Метрики за текущий месяц
        'monthly_stats',
        
        // Заметки и комментарии
        'notes',
        'performance_notes',
        
        // Дополнительные поля (flexible)
        'metadata',
    ];

    protected $casts = [
        'permissions' => 'json',
        'salary' => 'decimal:2',
        'salary_details' => 'json',
        'hired_at' => 'date',
        'probation_end_at' => 'date',
        'archived_at' => 'datetime',
        'total_revenue_generated' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'customer_satisfaction_score' => 'decimal:2',
        'task_completion_rate' => 'decimal:2',
        'attendance_rate' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'upsell_rate' => 'decimal:2',
        'cross_sell_rate' => 'decimal:2',
        'quality_score' => 'decimal:2',
        'speed_score' => 'decimal:2',
        'accuracy_score' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'average_product_rating' => 'decimal:2',
        'monthly_stats' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function __construct(
        private readonly Guard $guard
    ) {}

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Staff::class, 'manager_id');
    }

    // AI & Analytics Relations
    public function predictions()
    {
        return $this->hasMany(\App\Models\StaffPrediction::class);
    }

    public function skills()
    {
        return $this->hasMany(\App\Models\StaffSkill::class);
    }

    public function aiAnalytics()
    {
        return $this->hasMany(\App\Models\StaffAiAnalytics::class);
    }

    // Gamification Relations
    public function achievements()
    {
        return $this->hasMany(\App\Models\StaffAchievement::class);
    }

    public function points()
    {
        return $this->hasOne(\App\Models\StaffPoint::class);
    }

    public function challengeParticipants()
    {
        return $this->hasMany(\App\Models\StaffChallengeParticipant::class);
    }

    public function pointHistory()
    {
        return $this->hasMany(\App\Models\StaffPointHistory::class);
    }

    // Social Relations
    public function reviewsGiven()
    {
        return $this->hasMany(\App\Models\StaffReview::class, 'reviewer_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(\App\Models\StaffReview::class, 'reviewee_id');
    }

    public function mentoringsAsMentor()
    {
        return $this->hasMany(\App\Models\StaffMentoring::class, 'mentor_id');
    }

    public function mentoringsAsMentee()
    {
        return $this->hasMany(\App\Models\StaffMentoring::class, 'mentee_id');
    }

    public function thanksGiven()
    {
        return $this->hasMany(\App\Models\StaffThank::class, 'sender_id');
    }

    public function thanksReceived()
    {
        return $this->hasMany(\App\Models\StaffThank::class, 'receiver_id');
    }

    public function posts()
    {
        return $this->hasMany(\App\Models\StaffPost::class, 'author_id');
    }

    public function likes()
    {
        return $this->hasMany(\App\Models\StaffLike::class);
    }

    // Learning Relations
    public function courseEnrollments()
    {
        return $this->hasMany(\App\Models\StaffCourseEnrollment::class);
    }

    public function certifications()
    {
        return $this->hasMany(\App\Models\StaffCertification::class);
    }

    public function developmentPlans()
    {
        return $this->hasMany(\App\Models\StaffDevelopmentPlan::class);
    }

    public function quizResults()
    {
        return $this->hasMany(\App\Models\StaffQuizResult::class);
    }

    // Schedule Relations
    public function schedules()
    {
        return $this->hasMany(\App\Models\StaffSchedule::class);
    }

    public function shifts()
    {
        return $this->hasMany(\App\Models\StaffShift::class);
    }

    public function timeoffs()
    {
        return $this->hasMany(\App\Models\StaffTimeoff::class);
    }

    public function shiftSwapsAsOriginal()
    {
        return $this->hasMany(\App\Models\StaffShiftSwap::class, 'original_staff_id');
    }

    public function shiftSwapsAsReplacement()
    {
        return $this->hasMany(\App\Models\StaffShiftSwap::class, 'replacement_staff_id');
    }

    public function timeEntries()
    {
        return $this->hasMany(\App\Models\StaffTimeEntry::class);
    }

    // Wellness Relations
    public function checkins()
    {
        return $this->hasMany(\App\Models\StaffCheckin::class);
    }

    public function breaks()
    {
        return $this->hasMany(\App\Models\StaffBreak::class);
    }

    public function breakReminder()
    {
        return $this->hasOne(\App\Models\StaffBreakReminder::class);
    }

    public function wellnessMetrics()
    {
        return $this->hasMany(\App\Models\StaffWellnessMetric::class);
    }

    public function fitnessData()
    {
        return $this->hasMany(\App\Models\StaffFitnessData::class);
    }

    // Communication Relations
    public function messagesSent()
    {
        return $this->hasMany(\App\Models\StaffMessage::class, 'sender_id');
    }

    public function messagesReceived()
    {
        return $this->hasMany(\App\Models\StaffMessage::class, 'recipient_id');
    }

    public function messageGroupMemberships()
    {
        return $this->hasMany(\App\Models\StaffMessageGroupMember::class);
    }

    public function messageGroupsOwned()
    {
        return $this->hasMany(\App\Models\StaffMessageGroup::class, 'owner_id');
    }

    public function announcements()
    {
        return $this->hasMany(\App\Models\StaffAnnouncement::class, 'author_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeOnVacation(Builder $query): Builder
    {
        return $query->where('status', 'on_vacation');
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    public function scopeTopPerformers(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('total_revenue_generated')->limit($limit);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    public function getSalaryInRublesAttribute(): float
    {
        return (float) $this->salary / 100;
    }

    public function getTotalRevenueInRublesAttribute(): float
    {
        return (float) $this->total_revenue_generated / 100;
    }

    public function getAverageOrderValueInRublesAttribute(): float
    {
        return (float) $this->average_order_value / 100;
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->status === 'archived' || $this->deleted_at !== null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getHasVideoAvatarAttribute(): bool
    {
        return $this->video_avatar_generated && !empty($this->video_avatar_url);
    }

    // Methods
    public function archive(string $reason = ''): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
            'archive_reason' => $reason,
        ]);
    }

    public function restoreFromArchive(): void
    {
        $this->update([
            'status' => 'active',
            'archived_at' => null,
            'archive_reason' => null,
        ]);
    }

    public function calculateEfficiencyScore(): float
    {
        // Комплексная оценка эффективности на основе множества метрик
        $weights = [
            'customer_satisfaction' => 0.25,
            'task_completion' => 0.20,
            'attendance' => 0.15,
            'conversion' => 0.15,
            'quality' => 0.15,
            'speed' => 0.10,
        ];

        $normalizedScores = [
            'customer_satisfaction' => min(100, ($this->customer_satisfaction_score / 5) * 100),
            'task_completion' => $this->task_completion_rate,
            'attendance' => $this->attendance_rate,
            'conversion' => $this->conversion_rate,
            'quality' => ($this->quality_score / 5) * 100,
            'speed' => ($this->speed_score / 5) * 100,
        ];

        $totalScore = 0;
        foreach ($weights as $key => $weight) {
            $totalScore += ($normalizedScores[$key] ?? 0) * $weight;
        }

        return round($totalScore, 2);
    }

    public function updateMonthlyStats(array $stats): void
    {
        $this->update([
            'monthly_stats' => $stats,
        ]);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (app(Guard::class)->check() && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
