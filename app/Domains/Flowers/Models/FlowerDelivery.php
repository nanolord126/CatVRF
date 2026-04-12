<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerDelivery extends Model
{
    use HasFactory;

    use HasFactory, SoftDeletes;

        protected $table = 'flower_deliveries';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'order_id',
            'shop_id',
            'courier_name',
            'courier_phone',
            'current_location',
            'status',
            'assigned_at',
            'picked_up_at',
            'delivered_at',
            'delivery_notes',
            'route',
            'correlation_id',
        ];

        protected $casts = [
            'current_location' => 'json',
            'route' => 'json',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function order(): BelongsTo
        {
            return $this->belongsTo(FlowerOrder::class);
        }

        public function shop(): BelongsTo
        {
            return $this->belongsTo(FlowerShop::class);
        }
}
