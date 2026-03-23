<?php

declare(strict_types=1);

namespace App\Domains\Shop\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ShopProduct extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'shop_products';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'sku',
        'category',
        'price_kopeks',
        'compare_at_price_kopeks',
        'attributes',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'attributes' => 'json',
        'tags' => 'json',
    ];

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_kopeks / 100, 2, '.', ' ') . ' ₽';
    }
}
