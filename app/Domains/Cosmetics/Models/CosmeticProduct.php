<?php declare(strict_types=1);

namespace App\Domains\Cosmetics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CosmeticProduct extends Model
{
    use SoftDeletes;

    protected $table = 'cosmetic_products';

    protected $fillable = [
        'uuid', 'tenant_id', 'business_group_id', 'name', 'brand', 'sku', 'price',
        'ingredients', 'description', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'tags' => 'json',
        'ingredients' => 'json',
        'price' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', filament()?->getTenant()?->id ?? null));
    }
}
