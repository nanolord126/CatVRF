<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

final class FashionShoppingList extends Model
{
    use TenantScoped;

    protected $table = 'fashion_shopping_lists';

    protected $fillable = ['user_id', 'tenant_id', 'name', 'occasion', 'budget', 'status'];

    protected $casts = ['budget' => 'decimal:2'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FashionShoppingListItem::class, 'list_id');
    }
}
