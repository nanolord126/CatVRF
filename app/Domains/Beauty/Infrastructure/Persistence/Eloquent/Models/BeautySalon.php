<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Eloquent-модель салона красоты.
 *
 * Применяет глобальный scope по tenant_id.
 * Все суммы хранятся в копейках (int).
 *
 * @property int         $id
 * @property string      $uuid
 * @property int         $tenant_id
 * @property int|null    $business_group_id
 * @property string      $name
 * @property string      $address_full
 * @property float|null  $address_lat
 * @property float|null  $address_lon
 * @property array|null  $schedule
 * @property string|null $preview_photo_path
 * @property float       $rating
 * @property int         $review_count
 * @property bool        $is_verified
 * @property array|null  $tags
 * @property string|null $correlation_id
 */
final class BeautySalon extends Model
{
    use SoftDeletes;

    protected $table = 'beauty_salons';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'address_full',
        'address_lat',
        'address_lon',
        'schedule',
        'preview_photo_path',
        'rating',
        'review_count',
        'is_verified',
        'tags',
        'correlation_id',
    ];

    protected $hidden = [];

    protected $casts = [
        'schedule'    => 'json',
        'tags'        => 'json',
        'rating'      => 'float',
        'review_count' => 'integer',
        'address_lat' => 'float',
        'address_lon' => 'float',
        'is_verified' => 'boolean',
    ];

    /**
     * Регистрирует глобальный scope tenant_id и business_group_id.
     * Запрещено выполнять запросы без tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (function_exists('tenant') && tenant()) {
                $query->where('beauty_salons.tenant_id', tenant()->id);
            }
        });
    }

    // ===== Отношения =====

    public function masters(): HasMany
    {
        return $this->hasMany(BeautyMaster::class, 'salon_id', 'id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'salon_id', 'id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(BeautyAppointment::class, 'salon_id', 'id');
    }
}
