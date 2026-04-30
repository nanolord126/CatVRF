<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class FashionRental extends Model
{
    use TenantScoped;

    protected $table = 'fashion_rentals';

    protected $fillable = ['user_id', 'tenant_id', 'product_id', 'rental_days', 'rental_price', 'deposit', 'pickup_date', 'return_date', 'status', 'condition', 'damage_photos'];

    protected $casts = ['pickup_date' => 'datetime', 'return_date' => 'datetime', 'damage_photos' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
