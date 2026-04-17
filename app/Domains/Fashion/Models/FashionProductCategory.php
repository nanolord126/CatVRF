<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionProductCategory extends Model
{
    protected $table = 'fashion_product_categories';
    protected $fillable = [
        'product_id',
        'tenant_id',
        'primary_category',
        'secondary_categories',
        'tags',
        'style_profile',
        'season',
        'target_audience',
        'gender',
        'accessory_type',
        'material_type',
        'correlation_id',
    ];
    protected $casts = [
        'secondary_categories' => 'array',
        'tags' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }

    public function scopeScarves($query)
    {
        return $query->where('primary_category', 'scarves')
            ->orWhereIn('secondary_categories', ['scarves', 'shawls', 'wraps', 'neck_warmers']);
    }

    public function scopeHeadwear($query)
    {
        return $query->where('primary_category', 'headwear')
            ->orWhereIn('secondary_categories', ['hats', 'caps', 'beanies', 'berets', 'headbands', 'turbans']);
    }

    public function scopeCareProducts($query)
    {
        return $query->where('primary_category', 'care_products')
            ->orWhereIn('secondary_categories', ['fabric_care', 'leather_care', 'shoe_care', 'detergents', 'stain_removers']);
    }

    public function scopeUmbrellas($query)
    {
        return $query->where('primary_category', 'umbrellas')
            ->orWhereIn('secondary_categories', ['umbrellas', 'parasols', 'rain_gear']);
    }

    public function scopeAccessories($query)
    {
        return $query->where('primary_category', 'accessories')
            ->orWhere('accessory_type', '!=', null);
    }

    public function scopeMensAccessories($query)
    {
        return $query->where('gender', 'men')
            ->where(function ($q) {
                $q->where('primary_category', 'accessories')
                    ->orWhereIn('secondary_categories', ['belts', 'ties', 'bowties', 'cufflinks', 'wallets', 'bags', 'scarves', 'hats', 'gloves']);
            });
    }

    public function scopeWomensAccessories($query)
    {
        return $query->where('gender', 'women')
            ->where(function ($q) {
                $q->where('primary_category', 'accessories')
                    ->orWhereIn('secondary_categories', ['belts', 'handbags', 'clutches', 'jewelry', 'scarves', 'hats', 'gloves', 'hair_accessories']);
            });
    }
}
