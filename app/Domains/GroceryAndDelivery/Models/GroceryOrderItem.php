<?php declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class GroceryOrderItem extends Model
{

    protected $table = 'grocery_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'price_per_unit', 'total_price', 'correlation_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_per_unit' => 'integer',
        'total_price' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(GroceryOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(GroceryProduct::class);
    }
}
