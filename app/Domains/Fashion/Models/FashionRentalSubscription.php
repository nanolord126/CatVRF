<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionRentalSubscription extends Model
{
    protected $table = 'fashion_rental_subscriptions';
    protected $fillable = ['user_id', 'tenant_id', 'items_per_month', 'duration_months', 'plan_type', 'monthly_price', 'total_price', 'status', 'started_at', 'expires_at'];
    protected $casts = ['started_at' => 'datetime', 'expires_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
}
