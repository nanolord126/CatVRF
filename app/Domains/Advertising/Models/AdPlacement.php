<?php

namespace App\Domains\Advertising\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * AdPlacement Model - Слот для размещения рекламы (Production 2026).
 * 
 * Позиция на сайте/приложении для размещения рекламных баннеров.
 * Поддерживает:
 * - Ограничение по типам контента (баннер, видео, native)
 * - Размеры и параметры медиа
 * - Лимиты показов в день
 * - Минимальный/максимальный CPM бид
 * - Дневное отслеживание показов
 */
class AdPlacement extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'ad_placements';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_types' => 'array',
        'dimensions' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'allowed_types',
        'dimensions',
        'max_impressions_per_day',
        'min_cpm_bid',
        'max_cpm_bid',
        'metadata',
        'correlation_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->correlation_id) {
                $model->correlation_id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function banners(): HasMany
    {
        return $this->hasMany(AdBanner::class, 'placement_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(AdInteractionLog::class, 'placement_id');
    }

    /**
     * Проверить совместимость баннера с слотом.
     */
    public function canPlaceBanner(AdBanner $banner): bool
    {
        try {
            // Проверка типа контента
            $allowedTypes = $this->allowed_types ?? ['banner'];
            if (!in_array($banner->type ?? 'banner', $allowedTypes)) {
                return false;
            }

            // Проверка размеров
            if ($this->dimensions && isset($banner->width, $banner->height)) {
                if ($banner->width !== ($this->dimensions['width'] ?? null) 
                    || $banner->height !== ($this->dimensions['height'] ?? null)) {
                    return false;
                }
            }

            return $this->is_active;
        } catch (\Exception $e) {
            Log::warning('Error checking banner placement compatibility', [
                'placement_id' => $this->id,
                'banner_id' => $banner->id ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getTodayImpressions(): int
    {
        return $this->interactions()
            ->whereDate('created_at', today())
            ->where('event_type', 'impression')
            ->count();
    }

    public function isDailyLimitReached(): bool
    {
        if (!$this->max_impressions_per_day) {
            return false;
        }

        return $this->getTodayImpressions() >= $this->max_impressions_per_day;
    }
}
