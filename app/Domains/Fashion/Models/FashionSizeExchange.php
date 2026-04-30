<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class FashionSizeExchange extends Model
{
    use TenantScoped;

    protected $table = 'fashion_size_exchanges';

    protected $fillable = ['user_id', 'tenant_id', 'order_id', 'product_id', 'current_size', 'requested_size', 'reason', 'status', 'requested_at', 'processed_at'];

    protected $casts = ['requested_at' => 'datetime', 'processed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
