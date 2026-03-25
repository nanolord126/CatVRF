<?php declare(strict_types=1);

namespace 

/**
 * CateringOrder
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new CateringOrder();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\OfficeCatering\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
App\Domains\OfficeCatering\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class CateringOrder extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'catering_orders';
    protected $fillable = ['uuid', 'tenant_id', 'catering_company_id', 'client_id', 'correlation_id', 'office_name', 'office_address', 'delivery_datetime', 'person_count', 'status', 'total_kopecks', 'commission_kopecks', 'payout_kopecks', 'payment_status', 'menu_items_json', 'special_requests', 'tags'];

    protected $casts = ['person_count' => 'integer', 'total_kopecks' => 'integer', 'commission_kopecks' => 'integer', 'payout_kopecks' => 'integer', 'delivery_datetime' => 'datetime', 'menu_items_json' => 'json', 'tags' => 'json'];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function company() { return $this->belongsTo(CateringCompany::class, 'catering_company_id'); }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) => $q->where('catering_orders.tenant_id', tenant()->id));
    }
}
