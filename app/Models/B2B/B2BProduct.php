<?php

namespace App\Models\B2B;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Stancl\\Tenancy\\Database\\Concerns\\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class B2BProduct extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    protected $table = 'b2b_products';

    protected $fillable = [
        'manufacturer_id', 'category_id', 'brand_id', 'sku', 
        'name', 'description', 'unit', 'vertical', 'base_wholesale_price', 
        'min_order_quantity', 'stock_quantity', 'specifications', 
        'attributes', 'tags', 'correlation_id'
    ];

    protected $casts = [
        'specifications' => 'array',
        'attributes' => 'array',
        'base_wholesale_price' => 'decimal:2',
    ];

    public function manufacturer(): BelongsTo { return $this->belongsTo(B2BManufacturer::class, 'manufacturer_id'); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
}








