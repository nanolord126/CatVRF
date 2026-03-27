<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Психологическая клиника/центр.
 * 9-слойная архитектура 2026.
 */
final class PsychologicalClinic extends Model
{
    use SoftDeletes;

    protected $table = 'psy_clinics';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'metadata',
        'rating',
        'tags',
        'correlation_id',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'json',
        'tags' => 'json',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Глобальный скопинг по тенанту.
     */
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

    /**
     * Специалисты клиники.
     */
    public function psychologists(): HasMany
    {
        return $this->hasMany(Psychologist::class, 'clinic_id');
    }

    /**
     * Бронирования клиники через специалистов.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(PsychologicalBooking::class, 'clinic_id');
    }
}
