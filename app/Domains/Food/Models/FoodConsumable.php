<?php

declare(strict_types=1);


namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $restaurant_id
 * @property string $name
 * @property int $current_stock
 */
final class FoodConsumable extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'food_consumables';

    protected $fillable = [
        'tenant_id',
        'restaurant_id',
        'name',
        'unit',
        'current_stock',
        'min_stock_threshold',
        'price',
        'used_in_dishes',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => 'collection',
        'used_in_dishes' => 'collection',
        'current_stock' => 'integer',
        'min_stock_threshold' => 'integer',
        'price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
