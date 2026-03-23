<?php declare(strict_types=1);

namespace App\Domains\ReadyMeals\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель готового блюда — КАНОН 2026.
 */
final class ReadyMeal extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = 'ready_meals';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'description',
        'category', // 'breakfast', 'lunch', 'dinner', 'snack'
        'calories',
        'proteins',
        'fats',
        'carbs',
        'weight_grams',
        'price_kopecks',
        'current_stock',
        'min_stock_threshold',
        'expiry_hours',
        'ingredients', // jsonb
        'allergens', // jsonb
        'status', // 'active', 'inactive', 'out_of_stock'
        'is_vegan',
        'is_gluten_free',
        'tags',
        'meta',
    ];

    protected $casts = [
        'calories'      => 'integer',
        'price_kopecks' => 'integer',
        'current_stock' => 'integer',
        'ingredients'   => 'array',
        'allergens'     => 'array',
        'is_vegan'      => 'boolean',
        'is_gluten_free'=> 'boolean',
        'tags'          => 'array',
        'meta'          => 'array',
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
