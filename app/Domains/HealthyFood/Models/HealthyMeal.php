<?php declare(strict_types=1);

namespa

/**
 * HealthyMeal
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new HealthyMeal();
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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;

final class HealthyMeal extends Model
{
    use HasFactory, HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'healthy_meals';
    protected $fillable = [
        'tenant_id', 'business_group_id', 'uuid', 'correlation_id',
        'name', 'description', 'diet_type', 'calories',
        'protein_g', 'fat_g', 'carbs_g', 'price',
        'prep_time_min', 'allergens', 'photo_url', 'status', 'tags',
    ];
    protected $casts = [
        'calories'      => 'int',
        'protein_g'     => 'int',
        'fat_g'         => 'int',
        'carbs_g'       => 'int',
        'price'         => 'int',
        'prep_time_min' => 'int',
        'allergens'     => 'array',
        'tags'          => 'json',
    ];

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
