<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionOutOfStockEvent extends Model
{
    protected $table = 'fashion_out_of_stock_events';
    protected $fillable = ['product_id', 'tenant_id', 'estimated_lost_sales', 'duration_hours', 'correlation_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
