<?php declare(strict_types=1);

namespace App\Domains\Food\Grocery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GroceryOrder extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $fillable = [
            'tenant_id', 'store_id', 'user_id', 'inn',
            'business_card_id', 'items', 'total_price',
            'delivery_address', 'delivery_slot', 'status',
            'uuid', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'items' => 'json', 'tags' => 'json',
            'total_price' => 'decimal:2',
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
}
