<?php

namespace App\Models\Taxi;

use App\Models\BaseTenantModel;
use Illuminate\Database\Eloquent\Builder;

class TaxiSurgeZone extends BaseTenantModel
{
    protected $table = 'taxi_surge_zones';

    protected $fillable = [
        'name',
        'polygon_coords',
        'multiplier',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'polygon_coords' => 'array',
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Скоуп для получения активных зон наценки
     * (например, утро в центре города или пятничный вечер у баров)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Поиск зон с повышенным спросом для конкретных координат.
     */
    public static function getMultiplierAt(float $lat, float $lon): float
    {
        // В реальном приложении здесь используется ST_Contains в PostGIS или MySQL.
        // Для MVP проверяем наличие пересечения через JSON (или просто берем максимальный по городу).
        $zone = self::active()
            ->orderByDesc('multiplier')
            ->first();

        return (float) ($zone?->multiplier ?? 1.0);
    }
}
