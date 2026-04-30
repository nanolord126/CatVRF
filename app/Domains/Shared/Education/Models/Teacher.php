<?php

declare(strict_types=1);

namespace App\Domains\Education\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

final class Teacher extends Model
{
    use TenantScoped;

    protected $table = 'teachers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'specialization',
        'bio',
        'experience',
        'rating',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'uuid' => 'string',
        'rating' => 'float',
        'is_active' => 'boolean',
        'experience' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = [
        'id',
        'tenant_id',
    ];

    /**
     * Профиль пользователя в системе
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Все курсы преподавателя
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * Видеозвонки (запланированные живые занятия)
     */
    public function videoCalls(): HasMany
    {
        return $this->hasMany(VideoCall::class);
    }

    /**
     * КАНОН 2026: Изоляция тенанта
     */
    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function (Teacher $teacher) {
            $teacher->uuid = $teacher->uuid ?? (string) Str::uuid();
            $teacher->tenant_id = $teacher->tenant_id ?? (int) tenant()->id;
            $teacher->correlation_id = $teacher->correlation_id ?? (string) Str::uuid();
        });
    }
}
