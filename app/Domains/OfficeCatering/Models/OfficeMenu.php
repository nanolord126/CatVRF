<?php declare(strict_types=1);

namespace 

/**
 * OfficeMenu
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new OfficeMenu();
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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class OfficeMenu extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'office_menus';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'client_id', 'name', 'items', 'items_count',
        'price_per_serving', 'min_portions', 'active', 'tags',
    ];
    protected $casts = [
        'items'              => 'json',
        'items_count'        => 'int',
        'price_per_serving'  => 'int',
        'min_portions'       => 'int',
        'active'             => 'boolean',
        'tags'               => 'json',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(CorporateClient::class, 'client_id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
