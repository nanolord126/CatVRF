<?php

declare(strict_types=1);

namespace App\Domains\Beauty\BeautyServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BeautyStudio — Eloquent-модель студии красоты.
 *
 * Таблица: beauty_studios
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class BeautyStudio extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'beauty_studios';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'address',
        'services',
        'price_kopecks_per_minute',
        'rating',
        'is_verified',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'services'                => 'json',
        'price_kopecks_per_minute'=> 'integer',
        'rating'                  => 'float',
        'is_verified'             => 'boolean',
        'tags'                    => 'json',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('beauty_studios.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function appointments(): HasMany
    {
        return $this->hasMany(BeautyService::class, 'studio_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getPriceRubles(): float
    {
        return $this->price_kopecks_per_minute / 100;
    }

    public function calculatePrice(int $durationMinutes): int
    {
        return $this->price_kopecks_per_minute * $durationMinutes;
    }

    public function isVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    public function hasHighRating(): bool
    {
        return $this->rating >= 4.5;
    }
}

