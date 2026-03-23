<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

final class UserClickEvent extends Model
{
    protected $table = 'user_click_events';
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'page_url',
        'page_title',
        'click_x',
        'click_y',
        'screen_width',
        'screen_height',
        'element_selector',
        'browser',
        'device_type',
        'correlation_id',
        'recorded_at',
    ];

    protected $casts = [
        'click_x' => 'integer',
        'click_y' => 'integer',
        'screen_width' => 'integer',
        'screen_height' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function scopeForPage(Builder $query, string $url): Builder
    {
        return $query->where('page_url', $url);
    }

    public function scopeForDevice(Builder $query, string $type): Builder
    {
        return $query->where('device_type', $type);
    }

    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    /**
     * SECURITY: Анонимизация координат клика для защиты от отслеживания
     * Нормализуем до блоков 50x50 пиксельных (защита от идентификации позиций элементов)
     */
    public function getNormalizedCoordinates(): array
    {
        $blockSize = 50;
        return [
            'x' => floor($this->click_x / $blockSize) * $blockSize,
            'y' => floor($this->click_y / $blockSize) * $blockSize,
            'weight' => 1.0,
        ];
    }
}
