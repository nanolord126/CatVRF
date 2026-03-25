<?php declare(strict_types=1);

names

/**
 * TeaHouse
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TeaHouse();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\TeaHouses\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
pace App\Domains\TeaHouses\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class TeaHouse extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'tea_houses';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'address', 'phone', 'latitude', 'longitude', 'is_verified', 'commission_percent', 'min_order', 'tags'];
    protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'latitude' => 'float', 'longitude' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function teas() { return $this->hasMany(TeaType::class, 'house_id'); }
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function orders() { return $this->hasMany(TeaOrder::class, 'house_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('tea_houses.tenant_id', tenant()->id));
    }
}
