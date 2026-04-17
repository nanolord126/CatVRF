<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionABPriceTest extends Model
{
    protected $table = 'fashion_ab_price_tests';
    protected $fillable = ['tenant_id', 'product_id', 'control_price', 'test_price', 'control_group_size', 'test_group_size', 'control_conversions', 'test_conversions', 'control_revenue', 'test_revenue', 'status', 'winner', 'started_at', 'ends_at', 'completed_at', 'correlation_id'];
    protected $casts = ['control_price' => 'decimal:2', 'test_price' => 'decimal:2', 'control_revenue' => 'decimal:2', 'test_revenue' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
