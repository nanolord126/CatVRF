<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionB2BOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'fashion_b2b_orders';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'fashion_store_id',
            'buyer_inn',
            'total_amount',
            'status',
            'items_json',
            'correlation_id',
            'metadata',
        ];

        protected $casts = [
            'items_json' => 'json',
            'metadata' => 'json',
            'total_amount' => 'integer',
            'tenant_id' => 'integer',
        ];

        public function store(): BelongsTo
        {
            return $this->belongsTo(FashionStore::class, 'fashion_store_id');
        }
}
