<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionCrossSell extends Model
{
    protected $table = 'fashion_cross_sells';
    protected $fillable = ['source_product_id', 'target_product_id', 'user_id', 'tenant_id', 'occurred_at'];
    protected $casts = ['occurred_at' => 'datetime'];

    public function sourceProduct(): BelongsTo { return $this->belongsTo(FashionProduct::class, 'source_product_id'); }
    public function targetProduct(): BelongsTo { return $this->belongsTo(FashionProduct::class, 'target_product_id'); }
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
}
