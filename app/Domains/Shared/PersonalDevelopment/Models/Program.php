<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class Program extends Model
{
    use TenantScoped;

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

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(function (Program $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
            if (function_exists('tenant')) {
                $model->tenant_id = $model->tenant_id ?? (int) tenant()?->id;
            }
        });
    }
}
