<?php declare(strict_types=1);

namespace App\Domains\Flowers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class B2BFlowerStorefront extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'b2b_flower_storefronts';

    protected $fillable = [
        'tenant_id',
        'shop_id',
        'company_inn',
        'company_name',
        'company_address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'bulk_discounts',
        'min_order_items',
        'delivery_schedule',
        'is_verified',
        'is_active',
        'correlation_id',
    ];

    protected $casts = [
        'bulk_discounts' => 'json',
        'delivery_schedule' => 'json',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (filament()->getTenant()) {
                $query->where('tenant_id', filament()->getTenant()->id);
            }
        });
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(FlowerShop::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(B2BFlowerOrder::class, 'storefront_id');
    }
}
