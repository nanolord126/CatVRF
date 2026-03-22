<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Bouquet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'shop_id', 'name', 'description',
        'images', 'flowers_composition', 'price',
        'consumables_json', 'is_available', 'uuid',
        'correlation_id', 'tags',
    ];

    protected $casts = [
        'images' => 'json', 'flowers_composition' => 'json',
        'consumables_json' => 'json', 'tags' => 'json',
        'is_available' => 'boolean', 'price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn($q) =>
            $q->where('tenant_id', tenant()->id ?? 0)
        );
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(FlowerOrder::class, 'bouquet_id');
    }
}
