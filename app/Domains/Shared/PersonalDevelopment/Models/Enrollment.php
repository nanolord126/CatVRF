<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

final class Enrollment extends Model
{
    use TenantScoped;

    protected $table = 'pd_enrollments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'course_id',
        'program_id',
        'progress_percent',
        'status',
        'correlation_id',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
        'user_id' => 'integer',
        'course_id' => 'integer',
        'program_id' => 'integer',
        'tenant_id' => 'integer',
    ];

    protected $hidden = ['id'];

    /**
     * Пользователь, проходящий обучение.
     */
    public function user(): BelongsTo
    {
        /** @var User $userModel */
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Программа саморазвития.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * Курс саморазвития.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Контрольные точки прогресса (Milestones).
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'enrollment_id');
    }

    /**
     * Пересчет общего процента выполнения на основе вех.
     */
    public function updateProgressFromMilestones(): void
    {
        $total = $this->milestones()->count();
        if ($total === 0) {
            return;
        }

        $completed = $this->milestones()->where('is_completed', true)->count();
        $this->update([
            'progress_percent' => (int) (($completed / $total) * 100),
        ]);
    }

    /**
     * Booted method for global scoping and automatic UUID generation.
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(function (Enrollment $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = (int) tenant()?->id;
            }
        });
    }
}
