<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Модель Психолога / Терапевта.
 * 9-слойная архитектура 2026.
 */
final class Psychologist extends Model
{
    protected $table = 'psychologists';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'clinic_id',
        'full_name',
        'specialization',
        'therapy_types',
        'experience_years',
        'biography',
        'education',
        'base_price_per_hour',
        'metadata',
        'tags',
        'correlation_id',
        'is_available',
    ];

    protected $casts = [
        'therapy_types' => 'json',
        'education' => 'json',
        'metadata' => 'json',
        'tags' => 'json',
        'is_available' => 'boolean',
        'base_price_per_hour' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
            $model->tenant_id = auth()->user()->tenant_id ?? 0;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(PsychologicalClinic::class, 'clinic_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(PsychologicalService::class, 'psychologist_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(PsychologicalBooking::class, 'psychologist_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PsychologicalReview::class, 'psychologist_id');
    }
}
