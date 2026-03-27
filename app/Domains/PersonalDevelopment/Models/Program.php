<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * Program Model — Production Ready 2026
 * 
 * Программы долгосрочного развития (B2B и B2C).
 * Реализовано по доменному канону 2026.
 */
final class Program extends Model
{
    protected $table = 'pd_programs';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'coach_id',
        'title',
        'description',
        'level',
        'price_kopecks',
        'duration_days',
        'is_corporate',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'is_corporate' => 'boolean',
        'tags' => 'json',
        'price_kopecks' => 'integer',
        'duration_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });

        static::creating(function (Program $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            if (function_exists('tenant')) {
                $model->tenant_id = $model->tenant_id ?? (int) tenant('id');
            }
        });
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'coach_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'program_id');
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Возвращает цену одного дня участия в программе.
     */
    public function getDailyRate(): int
    {
        if ($this->duration_days <= 0) {
            return 0;
        }
        return (int) ($this->price_kopecks / $this->duration_days);
    }
}
