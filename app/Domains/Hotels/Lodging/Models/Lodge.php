<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Lodging\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Lodge — модель гостевого дома / лоджа CatVRF 2026.
 *
 * Tenant-aware модель для управления небольшими
 * гостевыми объектами (хостелы, дачи, лоджи).
 *
 * @package CatVRF
 * @version 2026.1
 * @see https://catvrf.ru/docs/lodge
 */
final class Lodge extends Model
{

    protected $table = 'lodges';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'owner_id',
        'correlation_id',
        'name',
        'address',
        'price_kopecks_per_night',
        'rooms',
        'is_verified',
        'tags',
    ];

    protected $casts = [
        'price_kopecks_per_night' => 'integer',
        'rooms' => 'integer',
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $query->where('lodges.tenant_id', tenant()->id);
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(LodgingBooking::class, 'lodge_id');
    }

    /**
     * Получить строковое представление модели.
     */
    public function __toString(): string
    {
        return sprintf('%s[id=%s, name=%s]', static::class, $this->id ?? 'new', $this->name ?? '');
    }

    /**
     * Отладочные данные для логирования.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'rooms' => $this->rooms,
            'is_verified' => $this->is_verified,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
