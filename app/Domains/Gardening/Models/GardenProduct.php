<?php
declare(strict_types=1);

namespace App\Domains\Gardening\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * GardenStore Model — Садовые магазины.
 *
 * @package CatVRF Gardening Vertical
 */
/**
 * GardenProduct Model — Садовые товары.
 *
 * @package CatVRF Gardening Vertical
 */
final class GardenProduct extends Model
{
    use HasFactory;

    protected $table = 'garden_products';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'store_id',
        'category_id',
        'name',
        'sku',
        'price_b2c',
        'price_b2b',
        'stock_quantity',
        'specifications',
        'is_published',
    ];

    protected $casts = [
        'specifications' => 'json',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            $query->where('garden_products.tenant_id', tenant()->id);
        });

        static::creating(function (Model $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GardenCategory::class, 'category_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(GardenStore::class, 'store_id');
    }

    public function plant(): HasOne
    {
        return $this->hasOne(GardenPlant::class, 'product_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(GardenReview::class, 'product_id');
    }
}
