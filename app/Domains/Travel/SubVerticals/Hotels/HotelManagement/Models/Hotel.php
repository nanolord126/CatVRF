<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\HotelManagement\Models;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\User;

/**
 * Hotel — модель гостиницы CatVRF 2026.
 *
 * Tenant-aware модель с глобальным скоупом, UUID
 * и каноничным форматом для маркетплейса.
 *
 * @version 2026.1
 *
 * @see https://catvrf.ru/docs/hotel
 */
final class Hotel extends Model
{
    protected $table = 'hotels';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'address',
        'room_types',
        'price_kopecks_per_night',
        'stars',
        'is_verified',
        'tags',
    ];

    protected $casts = [
        'room_types' => 'json',
        'price_kopecks_per_night' => 'integer',
        'stars' => 'integer',
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class, 'hotel_id');
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf('%s[id=%s, name=%s]', self::class, $this->id ?? 'new', $this->name ?? '');
    }

    /**
     * Отладочные данные для логирования.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'stars' => $this->stars,
            'is_verified' => $this->is_verified,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query): void {
            $query->where('hotels.tenant_id', tenant()->id);
        });

        self::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
