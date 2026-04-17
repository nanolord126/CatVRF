<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionShoppingListItem extends Model
{
    protected $table = 'fashion_shopping_list_items';
    protected $fillable = ['list_id', 'tenant_id', 'product_id', 'is_purchased'];
    protected $casts = ['is_purchased' => 'boolean'];

    public function list(): BelongsTo { return $this->belongsTo(FashionShoppingList::class, 'list_id'); }
    public function product(): BelongsTo { return $this->belongsTo(FashionProduct::class, 'product_id'); }
}
