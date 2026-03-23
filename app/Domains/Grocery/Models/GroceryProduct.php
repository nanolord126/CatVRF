<?php declare(strict_types=1);

namespace App\Domains\Grocery\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель товара супермаркета — КАНОН 2026.
 */
final class GroceryProduct extends Model
{
    use HasUuids, SoftDeletes, TenantScoped;

    protected $table = "grocery_products";

    protected $fillable = [
        "tenant_id",
        "store_id",
        "business_group_id",
        "uuid",
        "correlation_id",
        "name",
        "sku",
        "barcode",
        "description",
        "category_id",
        "brand",
        "manufacturer",
        "price_kopecks",
        "current_stock",
        "min_stock_threshold",
        "weight_grams",
        "dimensions_json",
        "is_refrigerated",
        "is_frozen",
        "is_alcoholic",
        "age_limit",
        "status",
        "tags",
        "meta",
    ];

    protected $casts = [
        "price_kopecks"     => "integer",
        "current_stock"      => "integer",
        "is_refrigerated"   => "boolean",
        "is_frozen"         => "boolean",
        "is_alcoholic"      => "boolean",
        "age_limit"         => "integer",
        "dimensions_json"   => "array",
        "tags"              => "array",
        "meta"              => "array",
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class, "store_id");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GroceryCategory::class, "category_id");
    }
}
