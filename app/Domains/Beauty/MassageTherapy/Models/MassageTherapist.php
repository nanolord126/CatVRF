<?php

declare(strict_types=1);

namespace App\Domains\Beauty\MassageTherapy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MassageTherapist — Eloquent-модель массажиста.
 *
 * Таблица: massage_therapists
 * Tenant-scoped: глобальный scope фильтрует по tenant_id.
 */
final class MassageTherapist extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'massage_therapists';

    /** @var array<int, string> */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'massage_types',
        'price_kopecks_per_minute',
        'rating',
        'is_verified',
        'tags',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'massage_types'            => 'json',
        'price_kopecks_per_minute' => 'integer',
        'rating'                   => 'float',
        'is_verified'              => 'boolean',
        'tags'                     => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query): void {
            $tenantId = function_exists('filament') ? filament()?->getTenant()?->getKey() : null;
            $tenantId ??= function_exists('tenant') && tenant() ? tenant()->id : null;

            if ($tenantId !== null) {
                $query->where('massage_therapists.tenant_id', $tenantId);
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function sessions(): HasMany
    {
        return $this->hasMany(MassageSession::class, 'therapist_id');
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

    public function hasMassageType(string $type): bool
    {
        return in_array($type, (array) ($this->massage_types ?? []), true);
    }
}

