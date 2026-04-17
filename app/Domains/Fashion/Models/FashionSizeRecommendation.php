<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionSizeRecommendation extends Model
{
    protected $table = 'fashion_size_recommendations';
    protected $fillable = ['user_id', 'tenant_id', 'product_id', 'recommended_size', 'confidence', 'recommended_at', 'correlation_id'];
    protected $casts = ['confidence' => 'decimal:2'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
