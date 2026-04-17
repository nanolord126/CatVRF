<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionVirtualWardrobe extends Model
{
    protected $table = 'fashion_virtual_wardrobe';
    protected $fillable = ['user_id', 'tenant_id', 'product_id', 'custom_tags', 'purchase_date', 'purchase_price', 'times_worn', 'last_worn_at', 'is_favorite', 'status'];
    protected $casts = ['custom_tags' => 'array', 'purchase_date' => 'date', 'last_worn_at' => 'datetime', 'is_favorite' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function product(): BelongsTo { return $this->belongsTo(FashionProduct::class, 'product_id'); }
}
