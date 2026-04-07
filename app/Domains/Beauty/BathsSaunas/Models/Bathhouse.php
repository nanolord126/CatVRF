<?php

declare(strict_types=1);

namespace App\Domains\Beauty\BathsSaunas\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bathhouse — Eloquent-модель бани/сауны.
 *
 * Таблица: bathhouses
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class Bathhouse extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'bathhouses';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'capacity',
        'price_kopecks_per_hour',
        'rating',
        'is_verified',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'capacity'               => 'integer',
        'price_kopecks_per_hour' => 'integer',
        'rating'                 => 'float',
        'is_verified'            => 'boolean',
        'tags'                   => 'json',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('bathhouses.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function bookings(): HasMany
    {
        return $this->hasMany(BathBooking::class, 'bath_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getPriceRubles(): float
    {
        return $this->price_kopecks_per_hour / 100;
    }

    public function calculatePrice(int $durationHours): int
    {
        return $this->price_kopecks_per_hour * $durationHours;
    }

    public function isVerified(): bool
    {
        return (bool) $this->is_verified;
    }

    public function hasCapacityFor(int $guests): bool
    {
        return $this->capacity >= $guests;
    }
}

