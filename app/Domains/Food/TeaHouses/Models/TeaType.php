<?php declare(strict_types=1);

names

/**
 * TeaType
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TeaType();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Food\TeaHouses\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\Food\TeaHouses\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class TeaType extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'tea_types';
    protected $fillable = ['uuid', 'tenant_id', 'house_id', 'correlation_id', 'name', 'price_kopecks', 'origin', 'brewing_temp', 'tags'];
    protected $casts = ['price_kopecks' => 'integer', 'brewing_temp' => 'integer', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function house() { return $this->belongsTo(TeaHouse::class, 'house_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('tea_types.tenant_id', tenant()->id));
    }
}
