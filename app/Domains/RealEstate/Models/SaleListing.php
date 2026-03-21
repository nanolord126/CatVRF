<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для объявления о продаже.
 * Production 2026.
 */
final class SaleListing extends Model
{
    use SoftDeletes;

    protected $table = 'sale_listings';
    protected $fillable = [
        'tenant_id', 'property_id', 'sale_price', 'commission_percent', 'auction',
        'auction_end_at', 'description', 'status', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'sale_price' => 'integer',
        'commission_percent' => 'float',
        'auction' => 'boolean',
        'auction_end_at' => 'datetime',
        'tags' => AsCollection::class,
    ];

    protected $hidden = ['deleted_at'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 0);
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
