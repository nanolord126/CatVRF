<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FashionShoppingList extends Model
{
    protected $table = 'fashion_shopping_lists';
    protected $fillable = ['user_id', 'tenant_id', 'name', 'occasion', 'budget', 'status'];
    protected $casts = ['budget' => 'decimal:2'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function items(): HasMany { return $this->hasMany(FashionShoppingListItem::class, 'list_id'); }
}
