<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionRental extends Model
{
    protected $table = 'fashion_rentals';
    protected $fillable = ['user_id', 'tenant_id', 'product_id', 'rental_days', 'rental_price', 'deposit', 'pickup_date', 'return_date', 'status', 'condition', 'damage_photos'];
    protected $casts = ['pickup_date' => 'datetime', 'return_date' => 'datetime', 'damage_photos' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function product(): BelongsTo { return $this->belongsTo(FashionProduct::class, 'product_id'); }
}
