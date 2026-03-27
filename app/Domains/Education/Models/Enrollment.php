<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель зачисления (Education Enrollment).
 * Учет прогресса, оплат и доступа.
 */
final class Enrollment extends Model
{
    protected $table = 'enrollments';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'course_id',
        'corporate_contract_id',
        'mode',
        'ai_path',
        'completed_at',
        'progress_percent',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'progress_percent' => 'integer',
        'ai_path' => 'json',
        'completed_at' => 'datetime',
        'mode' => 'string',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
    ];

    /**
     * КАНОН 2026: Инициализация и изоляция тенанта
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function (Enrollment $enrollment) {
            $enrollment->uuid = $enrollment->uuid ?? (string) Str::uuid();
            $enrollment->tenant_id = $enrollment->tenant_id ?? (int) tenant()->id;
            $enrollment->correlation_id = $enrollment->correlation_id ?? (string) Str::uuid();
        });
    }

    /**
     * Студент (пользователь)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Курс, на который зачислен студент
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Проверка активности доступа
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
