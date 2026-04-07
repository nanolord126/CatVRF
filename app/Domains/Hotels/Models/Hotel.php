<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Hotel — модель отеля CatVRF 2026 (основная).
 *
 * Содержит данные об отеле: название, адрес, звёзды, рейтинг,
 * расписание, номера и удобства. Tenant-scoped.
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/hotel
 */
final class Hotel extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'hotels';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'description',
        'address',
        'geo_point',
        'stars',
        'is_active',
        'schedule_json',
        'rating',
        'review_count',
        'correlation_id',
        'tags',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'schedule_json' => 'json',
        'tags' => 'json',
        'stars' => 'integer',
        'rating' => 'float',
        'review_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($builder): void {
            $builder->where('hotels.tenant_id', tenant()->id);
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'hotel_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'hotel_id');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(
            Amenity::class,
            'hotel_amenity_pivot',
            'hotel_id',
            'amenity_id',
        );
    }

    public function b2bContracts(): HasMany
    {
        return $this->hasMany(B2BContract::class, 'hotel_id');
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s[id=%s, name=%s, stars=%d]',
            static::class,
            $this->id ?? 'new',
            $this->name ?? '',
            $this->stars ?? 0,
        );
    }
}
