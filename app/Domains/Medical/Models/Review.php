<?php

declare(strict_types=1);

namespace App\Domains\Medical\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Builder;

/**
 * КАНОН 2026: Модель Отзыва (Medical Review).
 * Слой 2: Доменные Модели.
 */
final class Review extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'medical_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'clinic_id',
        'doctor_id',
        'appointment_id',
        'client_id',
        'rating',
        'comment',
        'is_verified_visit',
        'status', // pending, published, hidden, rejected
        'metadata',
        'tags',
        'correlation_id'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_visit' => 'boolean',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    /**
     * КАНОН: Global Scopes и События модели.
     */
    protected static function booted(): void
    {
        static::creating(function (Review $review) {
            $review->uuid = $review->uuid ?? (string)Str::uuid();
            $review->tenant_id = $review->tenant_id ?? (int)tenant()->id;
            $review->correlation_id = $review->correlation_id ?? (string)Str::uuid();
        });

        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Настройка логов для аудита.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rating', 'status', 'is_verified_visit'])
            ->logOnlyDirty()
            ->useLogName('medical_review_audit');
    }

    /**
     * Отношение: Клиника.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id');
    }

    /**
     * Отношение: Врач.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Отношение: Прием.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Отношение: Клиент (User).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Публикация отзыва.
     */
    public function publish(): void
    {
        $this->update(['status' => 'published']);
    }
}
