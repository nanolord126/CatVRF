<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent-модель мастера салона красоты.
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int         $salon_id
 * @property string      $name
 * @property array|null  $specialization
 * @property int         $experience_years
 * @property array|null  $schedule
 * @property string|null $photo_path
 * @property float       $rating
 * @property int         $review_count
 * @property bool        $is_active
 * @property array|null  $tags
 * @property string|null $correlation_id
 */
final class BeautyMaster extends Model
{
    use SoftDeletes;

    protected $table = 'beauty_masters';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'salon_id',
        'name',
        'specialization',
        'experience_years',
        'schedule',
        'photo_path',
        'rating',
        'review_count',
        'is_active',
        'tags',
        'correlation_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'specialization'   => 'json',
        'schedule'         => 'json',
        'tags'             => 'json',
        'rating'           => 'float',
        'review_count'     => 'integer',
        'experience_years' => 'integer',
        'is_active'        => 'boolean',
    ];

    /**
     * Глобальный scope: фильтрация по tenant_id мастера.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('beauty_masters.tenant_id', tenant()->id);
            }
        });
    }

    // ===== Отношения =====

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id', 'id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'master_id', 'id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BeautyAppointment::class, 'master_id', 'id');
    }
}
