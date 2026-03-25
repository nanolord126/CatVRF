<?php declare(strict_types=1);

namespa

/**
 * DietCompany
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new DietCompany();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\HealthyFood\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
ce App\Domains\HealthyFood\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class DietCompany extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'diet_companies';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', 'correlation_id', 'name', 'phone', 'address', 'is_verified', 'commission_percent', 'min_order', 'tags'];
    protected $casts = ['is_verified' => 'boolean', 'commission_percent' => 'float', 'min_order' => 'integer', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function meals() { return $this->hasMany(HealthyMeal::class, 'company_id'); }
    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function orders() { return $this->hasMany(HealthyMealOrder::class, 'company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('diet_companies.tenant_id', tenant()->id));
    }
}
