<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StockMovement extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $table = 'stock_movements';

        protected $fillable = [
            'inventory_item_id',
            'type',
            'quantity',
            'reason',
            'source_type',
            'source_id',
            'correlation_id',
            'created_by',
        ];

        protected $casts = [
            'quantity' => 'integer',
        ];

        public function inventoryItem(): BelongsTo
        {
            return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope("tenant_id", function ($query) {
                if (function_exists("tenant") && tenant("id")) {
                    $query->where("tenant_id", tenant("id"));
                }
            });
        }
}
