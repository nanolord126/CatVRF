<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FlowerSubscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_subscriptions';

    protected $fillable = [
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
            if (filament()->getTenant()) {
                $query->where('tenant_id', filament()->getTenant()->id);
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
