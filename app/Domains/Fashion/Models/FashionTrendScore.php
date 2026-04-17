<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionTrendScore extends Model
{
    protected $table = 'fashion_trend_scores';
    protected $fillable = ['product_id', 'trend_score', 'demand_velocity', 'correlation_id'];
    protected $casts = ['trend_score' => 'decimal:2', 'demand_velocity' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
