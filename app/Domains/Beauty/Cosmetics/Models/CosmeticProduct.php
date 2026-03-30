<?php declare(strict_types=1);

namespace App\Domains\Beauty\Cosmetics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CosmeticProduct extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
