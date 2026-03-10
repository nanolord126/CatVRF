<?php

namespace App\Domains\Food\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantOrder extends Model implements AIEnableEcosystemEntity
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    public function getAiAdjustedPrice(): float { return $this->total_amount; }
    public function getTrustScore(): int { return 90; }
    public function generateAiChecklist(): array { return ['All items prepared', 'Correct table number']; }

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'is_tax_inclusive' => 'boolean',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class, 'order_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'waiter_id');
    }
}

class RestaurantOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'notes' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RestaurantOrder::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(RestaurantMenuItem::class);
    }
}

class RestaurantTable extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(RestaurantOrder::class, 'table_id');
    }
}

class RestaurantMenuItem extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(RestaurantCategory::class);
    }
}

class RestaurantCategory extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantMenuItem::class, 'category_id');
    }
}
