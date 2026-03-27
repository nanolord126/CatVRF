<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * КАНОН 2026: Beauty Master Model (Layer 2)
 * Каждый мастер привязан к салону или работает в рамках тенанта.
 */
final class Master extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'masters';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'salon_id',
        'user_id',
        'full_name',
        'specialization',
        'experience_years',
        'rating',
        'review_count',
        'bio',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'specialization' => 'json',
        'tags' => 'json',
        'experience_years' => 'integer',
        'rating' => 'float',
        'review_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoping', function ($builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Отношения
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'master_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'master_id');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class, 'master_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'master_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MasterSchedule::class, 'master_id');
    }
}
