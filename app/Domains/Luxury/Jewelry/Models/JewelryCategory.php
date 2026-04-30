<?php

declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

/**
 * JewelryCategory (Layer 1/9)
 */
final class JewelryCategory extends Model
{
    use JewelryDomainTrait;
    use TenantScoped;

    protected $table = 'jewelry_categories';

    protected $fillable = ['uuid', 'tenant_id', 'name', 'slug', 'sort_order', 'correlation_id'];

    public function products(): HasMany
    {
        return $this->hasMany(JewelryProduct::class, 'category_id');
    }
}
