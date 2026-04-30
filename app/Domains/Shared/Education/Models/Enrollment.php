<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class Enrollment extends Model
{
    use TenantScoped;

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
     * Студент (пользователь)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * КАНОН 2026: Инициализация и изоляция тенанта
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function (Enrollment $enrollment) {
            $enrollment->uuid = $enrollment->uuid ?? (string) Str::uuid();
            $enrollment->tenant_id = $enrollment->tenant_id ?? (int) tenant()->id;
            $enrollment->correlation_id = $enrollment->correlation_id ?? (string) Str::uuid();
        });
    }
}
