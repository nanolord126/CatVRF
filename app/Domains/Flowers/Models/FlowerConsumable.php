<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerConsumable extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $fillable = [
            'tenant_id', 'shop_id', 'name', 'type',
            'current_stock', 'min_stock_threshold', 'unit',
            'price_per_unit', 'uuid', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'tags' => 'json', 'price_per_unit' => 'decimal:2',
            'current_stock' => 'integer', 'min_stock_threshold' => 'integer',
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
}
