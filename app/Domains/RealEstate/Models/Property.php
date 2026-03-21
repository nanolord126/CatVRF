<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для объекта недвижимости.
 * Production 2026.
 */
final class Property extends Model
{
    use SoftDeletes;

    protected $table = 'properties';
    protected $fillable = [
        'tenant_id', 'owner_id', 'address', 'geo_point', 'type', 'area', 'rooms',
        'floor', 'total_floors', 'condition', 'amenities', 'status', 'correlation_id', 'tags', 'metadata',
    ];

    protected $casts = [
        'geo_point' => 'json',
        'amenities' => AsCollection::class,
        'tags' => AsCollection::class,
        'metadata' => 'json',
        'area' => 'integer',
        'rooms' => 'integer',
        'floor' => 'integer',
        'total_floors' => 'integer',
    ];

    protected $hidden = ['deleted_at'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 0);
        });
    }

    public function rentalListing(): HasOne
    {
        return $this->hasOne(RentalListing::class, 'property_id');
    }

    public function saleListing(): HasOne
    {
        return $this->hasOne(SaleListing::class, 'property_id');
    }

    public function landPlot(): HasOne
    {
        return $this->hasOne(LandPlot::class, 'property_id');
    }

    public function viewingAppointments(): HasMany
    {
        return $this->hasMany(ViewingAppointment::class, 'property_id');
    }

    public function mortgageApplications(): HasMany
    {
        return $this->hasMany(MortgageApplication::class, 'property_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_id');
    }
}
