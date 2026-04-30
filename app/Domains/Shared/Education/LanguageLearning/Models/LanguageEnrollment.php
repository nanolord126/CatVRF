<?php

declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Models;

use Illuminate\Database\Eloquent\Model;

final class LanguageEnrollment extends Model
{
    protected $table = 'language_enrollments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'course_id',
        'paid_amount',
        'payment_status',
        'status',
        'progress_data',
        'correlation_id',
    ];

    protected $casts = [
        'progress_data' => 'json',
        'paid_amount' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(LanguageCourse::class, 'course_id');
    }

    /**
     * Получение процента прогресса обучения.
     */
    public function getProgressPercentAttribute(): int
    {
        return $this->progress_data['percent'] ?? 0;
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 1);
        });

        self::addGlobalScope('tenant_id', function ($query) {
            if (tenant()->id) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
