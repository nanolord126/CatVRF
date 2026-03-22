<?php declare(strict_types=1);

namespace App\Domains\Grocery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class GroceryProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'store_id', 'category_id', 'name',
        'description', 'images', 'price', 'stock',
        'unit', 'is_available', 'uuid', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'images' => 'json', 'tags' => 'json',
        'is_available' => 'boolean', 'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) =>
            $q->where('tenant_id', tenant()->id ?? 0)
        );
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(GroceryStore::class, 'store_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GroceryCategory::class);
    }
}
