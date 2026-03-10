<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCheck extends Model
{
    protected $fillable = [
        'check_date',
        'status', // draft, completed
        'notes',
        'user_id',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCheckItem::class);
    }
}
