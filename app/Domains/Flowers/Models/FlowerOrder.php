<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class FlowerOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'flower_orders';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'user_id',
        'order_number',
        'subtotal',
        'delivery_fee',
        'commission_amount',
        'total_amount',
        'recipient_name',
        'recipient_phone',
        'delivery_address',
        'delivery_location',
        'delivery_date',
        'delivery_time_slot',
        'message',
        'status',
        'payment_status',
        'correlation_id',
    ];

    protected $casts = [
        'delivery_location' => 'json',
        'delivery_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(FlowerOrderItem::class, 'order_id');
    }

    public function delivery(): HasMany
    {
        return $this->hasMany(FlowerDelivery::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(FlowerReview::class, 'order_id');
    }
}
