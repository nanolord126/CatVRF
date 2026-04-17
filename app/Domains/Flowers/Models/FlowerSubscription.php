<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerSubscription extends Model
{

    use HasFactory, SoftDeletes;

        protected $table = 'flower_subscriptions';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'shop_id',
            'user_id',
            'subscription_name',
            'description',
            'products',
            'frequency',
            'price_per_delivery',
            'commission_amount',
            'start_date',
            'end_date',
            'deliveries_completed',
            'deliveries_remaining',
            'status',
            'payment_status',
            'correlation_id',
        ];

        protected $casts = [
            'products' => 'json',
            'start_date' => 'date',
            'end_date' => 'date',
            'price_per_delivery' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        public function shop(): BelongsTo
        {
            return $this->belongsTo(FlowerShop::class);
        }
}
