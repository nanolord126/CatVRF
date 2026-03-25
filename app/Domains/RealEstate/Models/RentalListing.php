declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для объявления об аренде.
 * Production 2026.
 */
final class RentalListing extends Model
{
    use SoftDeletes;

    protected $table = 'rental_listings';
    protected $fillable = [
        'tenant_id', 'property_id', 'rent_price_month', 'deposit', 'lease_term_min',
        'lease_term_max', 'is_furnished', 'pets_allowed', 'description', 'status', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'rent_price_month' => 'integer',
        'deposit' => 'integer',
        'lease_term_min' => 'integer',
        'lease_term_max' => 'integer',
        'is_furnished' => 'boolean',
        'pets_allowed' => 'boolean',
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
