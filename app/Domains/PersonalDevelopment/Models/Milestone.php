<?php declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Milestone extends Model
{
    use HasFactory;

    protected $table = 'pd_milestones';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'enrollment_id',
        'title',
        'requirements',
        'is_completed',
        'completed_at',
        'correlation_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'enrollment_id' => 'integer',
        'tenant_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        static::creating(function (Milestone $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            if (function_exists('tenant')) {
                $model->tenant_id = $model->tenant_id ?? (int) tenant()?->id;
            }
        });

        // После обновления вехи также обновляем общий процент выполнения в Enrollment
        static::updated(function (Milestone $model) {
            $model->enrollment->updateProgressFromMilestones();
        });
    }

    /**
     * Запись на программу, к которой относится веха.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    /**
     * Пометка вехи как выполненной.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'correlation_id' => (string) Str::uuid()
        ]);
    }
}
