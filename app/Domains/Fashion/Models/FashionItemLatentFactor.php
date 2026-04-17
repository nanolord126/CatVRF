<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionItemLatentFactor extends Model
{
    protected $table = 'fashion_item_latent_factors';
    protected $fillable = ['product_id', 'tenant_id', 'factors', 'correlation_id'];
    protected $casts = ['factors' => 'array'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
